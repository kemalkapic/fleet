# Fleet SaaS Architecture Plan

Ovaj dokument je prvi plan za novu multi-tenant SaaS aplikaciju za fleet i vehicle management. Ne implementira cijelu aplikaciju; služi kao dogovor oko arhitekture prije početka rada.

## 1. Symfony Verzija I Paketi

Repo trenutno nema novu aplikaciju, ali ima temu u `tema/Symfony/Admin` i `tema/Symfony/Starter`. `Admin` varijanta je bolja početna vizualna baza za SaaS dashboard jer ima više Twig stranica, layout elemenata, tabela, formi i admin komponenti.

Preporuka:

- Ako server/lokalno okruženje ima PHP 8.4+: Symfony 8.1, jer je trenutna stabilna verzija.
- Ako želimo mirniji SaaS foundation i dužu podršku: Symfony 7.4 LTS, jer traži PHP 8.2 i ima duži support window.
- Tema je Symfony 6.3 projekat, pa je ne bih koristio kao aplikacijski skeleton jedan-na-jedan. Koristio bih je kao UI asset/template izvor i napravio čist Symfony skeleton.

Minimalni MVP paketi:

- `symfony/framework-bundle`
- `symfony/twig-bundle`
- `symfony/security-bundle`
- `symfony/form`
- `symfony/validator`
- `symfony/asset`
- `symfony/mailer`
- `symfony/messenger`
- `doctrine/orm`
- `doctrine/doctrine-bundle`
- `doctrine/doctrine-migrations-bundle`
- `symfony/maker-bundle` samo za dev
- `phpunit/phpunit` ili Symfony test pack za testove

Za frontend bih krenuo pragmatično: koristiti assets iz teme kroz Twig i public assets, bez komplikovanja modernim frontend buildom dok nam ne zatreba.

## 2. High-Level Arhitektura

Aplikacija ostaje standardni Symfony monolit:

- Twig web UI za MVP.
- Doctrine ORM za poslovne entitete.
- Symfony Security za login, role i voters.
- Servisi za poslovnu logiku, posebno tenant/module/OCR/file storage.
- Messenger samo gdje stvarno ima smisla, najviše za OCR obradu ako bude spora.
- Bez API-first arhitekture u MVP-u, ali controlleri ne smiju nositi poslovna pravila kako bismo kasnije mogli dodati API.

Predloženi slojevi:

- `Controller`: prima request, provjerava access, delegira servisima.
- `Form`: Symfony forme za unos i review OCR podataka.
- `Entity`: Doctrine model.
- `Repository`: tenant-aware query metode.
- `Security/Voter`: pristup entitetima i tenant ownership.
- `Service/Tenant`: current company context i tenant guard.
- `Service/Module`: provjera enabled modula.
- `Service/Ocr`: OCR provider, parseri i DTO mapping.
- `Service/Storage`: sigurni upload/download fajlova.

## 3. Entity Model Za Milestone 1

Osnovni model treba ostati mali:

```text
Company
  id
  name
  status: active|inactive|trial|suspended
  createdAt
  updatedAt

User
  id
  company nullable for ROLE_SUPER_ADMIN
  email
  password
  roles json
  firstName
  lastName
  isActive
  createdAt
  updatedAt

CompanyModule
  id
  company
  module enum/string
  enabled bool
  enabledAt nullable

Vehicle
  id
  company
  registrationNumber
  vin nullable
  manufacturer nullable
  model nullable
  productionYear nullable
  currentMileage nullable
  status: active|inactive|sold|archived
  notes nullable
  createdAt
  updatedAt

VehicleDocument
  id
  company
  vehicle
  type enum/string
  documentNumber nullable
  validFrom nullable
  validUntil nullable
  storageKey
  originalFilename nullable
  mimeType
  sizeBytes
  extractedData json nullable
  createdAt
  updatedAt

Driver
  id
  company
  firstName
  lastName
  phone nullable
  email nullable
  status: active|inactive
  notes nullable
  createdAt
  updatedAt

DriverDocument
  id
  company
  driver
  type enum/string
  documentNumber nullable
  validFrom nullable
  validUntil nullable
  storageKey
  originalFilename nullable
  mimeType
  sizeBytes
  extractedData json nullable
  createdAt
  updatedAt
```

Važno:

- `VehicleDocument` i `DriverDocument` imaju svoj `company`, iako se može doći do kompanije preko vehicle/driver. To olakšava tenant filtere, upite za buduće reminders i sigurnosne provjere.
- Ne uvoditi još `Subscription`, `Plan`, `Fuel`, `WorkOrder`, `Maintenance`, `Report`.
- Module nazive držati kao centralni enum/value object, ne kao proizvoljan string rasut po kodu.

## 4. Multi-Tenant Strategija

MVP ide sa shared app + shared database + `company_id` na svim tenant entitetima. To je dobar izbor za rani SaaS jer je jednostavniji za deploy, backup, migracije i razvoj.

Sigurnosna pravila:

- Svaki tenant-owned entitet ima `company_id`.
- Repozitoriji imaju metode kao `findOneForCompany($id, Company $company)`, `findForCompany(Company $company)`.
- Controlleri nikad ne koriste obični `find($id)` za tenant podatke.
- Voters provjeravaju da korisnikova company odgovara entitetovoj company.
- Super admin pristup je eksplicitan i odvojen kroz `/admin` ili `/platform` dio aplikacije.
- Forms i dropdowni učitavaju samo entitete trenutne kompanije.
- Kod kreiranja, company se postavlja iz current user contexta, ne iz requesta.

Primjer problema koji izbjegavamo: company admin ručno promijeni `/vehicles/123/edit` u ID vozila druge kompanije. Query `findOneForCompany(123, currentCompany)` vraća `null`, a voter dodatno brani pristup ako entitet ipak nekako dođe do controllera.

## 5. Authorization Strategija

Role:

- `ROLE_SUPER_ADMIN`: platforma, kompanije, module, status kompanija.
- `ROLE_COMPANY_ADMIN`: upravljanje podacima svoje kompanije.
- `ROLE_DRIVER`: ograničen pristup budućim vlastitim zadacima/dokumentima.

Pristup:

- Route-level role checks za velike cjeline.
- Voters za entitete: `CompanyVoter`, `VehicleVoter`, `DriverVoter`, `DocumentVoter`, `UserVoter`.
- Servis `TenantContext` vraća trenutnu kompaniju ili baca exception ako tenant kontekst nije validan.
- Super admin ne smije slučajno raditi kao tenant user bez eksplicitno odabrane kompanije u platform/admin kontekstu.

Kasnije role kao `ROLE_DISPATCHER`, `ROLE_ACCOUNTANT`, `ROLE_FLEET_MANAGER` dodajemo kroz voters i permission mape, bez promjene tenant modela.

## 6. Module System

Module definisati centralno:

```php
enum Module: string
{
    case VEHICLES = 'vehicles';
    case DRIVERS = 'drivers';
    case DOCUMENTS = 'documents';
    case REMINDERS = 'reminders';
    case MAINTENANCE = 'maintenance';
    case FUEL = 'fuel';
    case EXPENSES = 'expenses';
    case WORK_ORDERS = 'work_orders';
    case REPORTS = 'reports';
    case ACCOUNTING = 'accounting';
    case OCR = 'ocr';
}
```

Po kompaniji se čuva `CompanyModule`.

Backend enforcement:

- `ModuleChecker::isEnabled(Company $company, Module $module): bool`
- Controller attribute ili eksplicitna provjera u controlleru za modul.
- Servisi koji rade business akcije takođe provjeravaju modul za osjetljive operacije.
- Ako je modul disabled, vraća se 403, ne samo sakriven meni.

Twig:

- Twig extension/function: `module_enabled('vehicles')`
- Navigacija skriva linkove koji nisu omogućeni.
- Skrivanje linka je UX, ne sigurnost.

## 7. OCR Arhitektura

OCR mora biti odvojen u tri nivoa:

```text
Uploaded image/document
  -> OcrProviderInterface
  -> RawOcrResult
  -> DocumentParserInterface
  -> Structured DTO
  -> Review form
  -> User confirmation
  -> Persistence
```

Servisi/DTO:

- `OcrProviderInterface`
  - `process(FileReference $file): RawOcrResult`
- `RawOcrResult`
  - raw text
  - blocks/lines if provider supports them
  - confidence values if available
  - provider metadata
- `VehicleRegistrationParser`
  - `parse(RawOcrResult $result): VehicleRegistrationOcrData`
- `DrivingLicenceParser`
  - `parse(RawOcrResult $result): DrivingLicenceOcrData`
- `VehicleRegistrationOcrData`
  - candidate vehicle fields
  - candidate document fields
  - confidence map
- `DrivingLicenceOcrData`
  - candidate driver fields
  - candidate licence document fields
  - categories
  - confidence map

OCR flow:

1. User uploaduje/slika dokument.
2. Fajl se validira i pohrani privremeno ili kao pending document upload.
3. OCR provider vrati raw result.
4. Parser napravi strukturirane kandidate.
5. User vidi formu sa popunjenim poljima.
6. User sve pregleda i potvrdi.
7. Tek tada se kreira `Vehicle`/`Driver` i odgovarajući document.

OCR nikad ne smije automatski spasiti vehicle/driver bez ljudske potvrde.

## 8. Secure File Storage

Ne čuvati dokumente kao DB blobove.

MVP:

- private filesystem storage, npr. `var/storage/documents/{companyUuid}/{randomKey}`
- DB čuva samo `storageKey`, metadata i extracted JSON
- fajlovi nisu u `public/`
- download ide kroz kontroler koji radi authorization

Kasnije:

- `DocumentStorageInterface`
- `LocalDocumentStorage`
- budući `S3DocumentStorage`

Validacija:

- MIME allowlist: JPEG, PNG, PDF gdje treba.
- max file size.
- random filenames/storage keys.
- antivirus/image processing kasnije ako bude potrebno.
- audit log kasnije za osjetljive dokumente.
- delete/retention pravila odgoditi, ali ne dizajnirati public URL model.

## 9. Mobile Browser Camera/Upload Flow

Za MVP obični web browser je dovoljan:

- forma koristi `<input type="file" accept="image/*,application/pdf" capture="environment">`
- na mobitelu korisnik može slikati dokument kamerom ili izabrati postojeći fajl
- upload šalje sliku backendu
- backend validira fajl i pokreće OCR
- ako je OCR brz, može biti sync
- ako je spor, koristiti Messenger job i prikazati processing screen
- rezultat ide u review formu
- user koriguje i potvrđuje

UI mora biti responsive, sa velikim touch targetima, jasnim upload stanjem i pregledom slike/dokumenta gdje je moguće.

## 10. Predložena Direktorij Struktura

```text
src/
  Controller/
    Platform/
    Company/
    Security/
  Entity/
  Enum/
  Form/
  Repository/
  Security/
    Voter/
  Service/
    Tenant/
    Module/
    Ocr/
    Storage/
  Dto/
    Ocr/
templates/
  base.html.twig
  platform/
  company/
  security/
  components/
public/
  assets/
var/
  storage/
```

Temu iz `tema/Symfony/Admin` prebaciti selektivno:

- CSS/JS/font/image assets u `public/assets`
- base layout pretvoriti u `templates/base.html.twig`
- sidebar/header razbiti u Twig partiale
- demo stranice ne prebacivati u aplikaciju osim kao referencu

## 11. Milestone 1 Faze

Phase 1 - Symfony foundation

- kreirati clean Symfony app
- spojiti Twig
- ubaciti osnovni layout iz teme
- podesiti Doctrine i env

Phase 2 - Security

- login/logout
- `User` entity
- roles
- password hashing
- osnovni dashboard redirect po roli

Phase 3 - Multi-tenancy

- `Company`
- `TenantContext`
- tenant-aware repositories
- voters za tenant ownership
- testovi za zabranu cross-company pristupa

Phase 4 - Platform admin

- super admin company CRUD
- aktivacija/deaktivacija kompanija
- company users osnovno upravljanje

Phase 5 - Modules

- `CompanyModule`
- enum modula
- module checker
- backend 403 provjere
- Twig navigation checks

Phase 6 - Vehicles

- vehicle CRUD
- tenant isolation
- responsive forms/listing

Phase 7 - Drivers

- driver CRUD
- tenant isolation
- responsive forms/listing

Phase 8 - Documents

- `VehicleDocument`
- `DriverDocument`
- secure upload/download
- document type, dates, extracted JSON

Phase 9 - OCR abstraction

- provider interface
- fake/local provider za razvoj
- raw result DTO
- parser interfaces

Phase 10 - Vehicle OCR

- upload/scan registration document
- parse candidate fields
- review form
- save vehicle + vehicle document after confirmation

Phase 11 - Driver OCR

- upload/scan driving licence
- parse candidate fields
- review form
- save driver + driver document after confirmation

Phase 12 - Hardening/tests

- authorization tests
- module enforcement tests
- upload validation tests
- basic happy path browser tests

## 12. Najveći Rizici

- Cross-tenant data leak kroz obični `find($id)`, dropdown query ili download URL.
- Module enforcement samo u UI-u, bez backend provjere.
- Dokumenti javno dostupni iz `public/uploads`.
- OCR greške koje se automatski spase bez pregleda korisnika.
- Previše generičkih/future entiteta prije nego MVP stvarno treba te funkcije.
- Super admin i tenant context pomiješani bez jasnih granica.
- Tema sadrži mnogo demo stranica i asseta; treba je očistiti, ne prenijeti sve.

## 13. Odluke Sada Vs Kasnije

Odlučiti sada:

- Symfony 8.1 ako imamo PHP 8.4, ili Symfony 7.4 LTS ako želimo stabilniji support i PHP 8.2 kompatibilnost.
- Shared database multi-tenancy sa `company_id`.
- Private file storage iza autorizovanih controller download ruta.
- Backend module enforcement od prvog dana.
- OCR provider abstraction + parser separation.

Namjerno odgoditi:

- Billing, planovi i payment provider.
- Native mobile app.
- GPS/telematics.
- Work orders/trips.
- Fuel, expenses, maintenance.
- Advanced reports/accounting exports.
- S3/object storage implementacija, ali ostaviti interface.
- Final OCR provider izbor, dok ne znamo kvalitet za lokalne dokumente i cijene.

## Predloženi Sljedeći Korak

Ako odobravaš ovaj plan, sljedeći mali korak je napraviti čist Symfony skeleton, prebaciti osnovni layout/assets iz `tema/Symfony/Admin`, i postaviti početni login/dashboard bez poslovnih modula.
