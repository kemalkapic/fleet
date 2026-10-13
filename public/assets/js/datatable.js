/*
-------------------------------------------------------------------------
* Template Name    : Sliced - Tailwind CSS Admin & Dashboard Template   * 
* Author           : SRBThemes                                          *
* Version          : 1.0.0                                              *
* Created          : May 2023                                           *
* File Description : Main Js file of the template                       *
*------------------------------------------------------------------------
*/

let data = [
    {
        name: "Brielle Kuphal",
        job: "Global Metrics Developer",
        country: "Tunisia",
        year: 1969,
    },
    {
        name: "Barney Murray",
        job: "Product Solutions Executive",
        country: "Haiti",
        year: 1970,
    },
    {
        name: "Ressie Ruecker",
        job: "Forward Factors Orchestrator",
        country: "Georgia",
        year: 1967,
    },
    {
        name: "Teresa Mertz",
        job: "Dynamic Division Director",
        country: "Bahrain",
        year: 1953,
    },
    {
        name: "Chelsey Hackett",
        job: "Senior Accountability Architect",
        country: "Malta",
        year: 1962,
    },
    {
        name: "Tatyana Metz",
        job: "Regional Metrics Coordinator",
        country: "Sierra Leone",
        year: 1983,
    },
    {
        name: "Oleta Harvey",
        job: "District Usability Producer",
        country: "Swaziland",
        year: 1963,
    },
    {
        name: "Bette Haag",
        job: "Dynamic Program Orchestrator",
        country: "Guyana",
        year: 1974,
    },
    {
        name: "Meda Ebert",
        job: "Product Division Orchestrator",
        country: "Saint Kitts and Nevis",
        year: 1954,
    },
    {
        name: "Elissa Stroman",
        job: "Dynamic Applications Developer",
        country: "Andorra",
        year: 1978,
    },
    {
        name: "Sid Swaniawski",
        job: "Forward Interactions Designer",
        country: "Gibraltar",
        year: 1979,
    },
    {
        name: "Madonna Hahn",
        job: "Customer Configuration Specialist",
        country: "Pakistan",
        year: 1948,
    },
    {
        name: "Waylon Kihn",
        job: "National Security Facilitator",
        country: "China",
        year: 1958,
    },
    {
        name: "Jaunita Lindgren",
        job: "Senior Identity Consultant",
        country: "Sweden",
        year: 1958,
    },
    {
        name: "Lenora MacGyver",
        job: "Corporate Factors Manager",
        country: "Vietnam",
        year: 1951,
    },
    {
        name: "Ole Collier",
        job: "Corporate Factors Assistant",
        country: "Cape Verde",
        year: 1967,
    },
    {
        name: "Taurean Koelpin",
        job: "District Intranet Officer",
        country: "Northern Mariana Islands",
        year: 1980,
    },
    {
        name: "Thalia Yost",
        job: "Dynamic Usability Director",
        country: "French Southern Territories",
        year: 1948,
    },
    {
        name: "Okey Kling",
        job: "District Metrics Manager",
        country: "Poland",
        year: 1953,
    },
    {
        name: "Darrick Ortiz",
        job: "Chief Metrics Agent",
        country: "Tajikistan",
        year: 1990,
    },
    {
        name: "Eulalia Vandervort",
        job: "Future Creative Manager",
        country: "Spain",
        year: 1990,
    },
    {
        name: "Genevieve Pouros",
        job: "Investor Identity Facilitator",
        country: "British Indian Ocean Territory (Chagos Archipelago)",
        year: 1983,
    },
    {
        name: "Horacio Purdy",
        job: "Direct Accountability Producer",
        country: "Slovakia (Slovak Republic)",
        year: 1968,
    },
    {
        name: "Eleazar Konopelski",
        job: "National Usability Engineer",
        country: "Cayman Islands",
        year: 1954,
    },
    {
        name: "Herminia Effertz",
        job: "Central Usability Manager",
        country: "Saudi Arabia",
        year: 1954,
    },
    {
        name: "Brian Hermann",
        job: "Central Optimization Coordinator",
        country: "Papua New Guinea",
        year: 1952,
    },
    {
        name: "Wellington Barrows",
        job: "Customer Identity Engineer",
        country: "Zimbabwe",
        year: 1957,
    },
    {
        name: "David Rosenbaum",
        job: "Global Research Administrator",
        country: "Mayotte",
        year: 1958,
    },
    {
        name: "Pinkie Reilly",
        job: "Internal Data Director",
        country: "Isle of Man",
        year: 1973,
    },
    {
        name: "Asha Bartell",
        job: "Global Security Associate",
        country: "Tonga",
        year: 1991,
    },
    {
        name: "Alexane Bode",
        job: "Product Integration Planner",
        country: "Honduras",
        year: 1959,
    },
    {
        name: "Estelle Bode",
        job: "Lead Marketing Producer",
        country: "Dominica",
        year: 1962,
    },
    {
        name: "Jeromy Mayer",
        job: "Direct Accountability Director",
        country: "Maldives",
        year: 1973,
    },
    {
        name: "Ephraim Wiegand",
        job: "Direct Identity Administrator",
        country: "Australia",
        year: 1946,
    },
    {
        name: "Jarrett Grimes",
        job: "International Web Strategist",
        country: "Tunisia",
        year: 1986,
    },
    {
        name: "Jeffry Macejkovic",
        job: "National Paradigm Specialist",
        country: "Taiwan",
        year: 1959,
    },
    {
        name: "Janelle Stroman",
        job: "National Accounts Engineer",
        country: "Denmark",
        year: 1974,
    },
    {
        name: "Camille Considine",
        job: "Central Solutions Planner",
        country: "Chile",
        year: 1975,
    },
    {
        name: "Amani Schinner",
        job: "Corporate Metrics Manager",
        country: "Sri Lanka",
        year: 1981,
    },
    {
        name: "Dustin Bahringer",
        job: "Regional Research Designer",
        country: "Russian Federation",
        year: 1988,
    },
    {
        name: "Mary Sanford",
        job: "Chief Applications Consultant",
        country: "Isle of Man",
        year: 1959,
    },
    {
        name: "Itzel Skiles",
        job: "Dynamic Factors Strategist",
        country: "Western Sahara",
        year: 1981,
    },
    {
        name: "Melyssa Denesik",
        job: "Customer Security Consultant",
        country: "Libyan Arab Jamahiriya",
        year: 1960,
    },
    {
        name: "Elenora McLaughlin",
        job: "Legacy Paradigm Engineer",
        country: "Swaziland",
        year: 1954,
    },
    {
        name: "Chet Lueilwitz",
        job: "Chief Implementation Producer",
        country: "Togo",
        year: 1947,
    },
    {
        name: "Adrian Ondricka",
        job: "National Optimization Orchestrator",
        country: "Monaco",
        year: 1991,
    },
    {
        name: "Quincy West",
        job: "Regional Paradigm Developer",
        country: "Qatar",
        year: 1979,
    },
    {
        name: "Bernardo Krajcik",
        job: "Lead Intranet Architect",
        country: "Japan",
        year: 1959,
    },
    {
        name: "Aiyana Kshlerin",
        job: "Forward Optimization Orchestrator",
        country: "Netherlands",
        year: 1971,
    },
    {
        name: "Ambrose Anderson",
        job: "Central Solutions Executive",
        country: "Moldova",
        year: 1986,
    },
    {
        name: "Cassandre Volkman",
        job: "Lead Quality Director",
        country: "Israel",
        year: 1975,
    },
    {
        name: "Vivien Hettinger",
        job: "Investor Assurance Producer",
        country: "Suriname",
        year: 1992,
    },
    {
        name: "Katelynn Reichert",
        job: "Customer Brand Designer",
        country: "Chad",
        year: 1972,
    },
    {
        name: "Ibrahim Stroman",
        job: "Direct Marketing Planner",
        country: "Qatar",
        year: 1984,
    },
    {
        name: "Edyth McCullough",
        job: "Human Integration Manager",
        country: "Turks and Caicos Islands",
        year: 1992,
    },
];
window.dataTable = function () {
    return {
        items: [],
        view: 5,
        searchInput: "",
        pages: [],
        offset: 5,
        pagination: {
            total: data.length,
            lastPage: Math.ceil(data.length / 3),
            perPage: 3,
            currentPage: 1,
            from: 1,
            to: 1 * 5,
        },
        currentPage: 1,
        sorted: {
            field: "name",
            rule: "asc",
        },
        initData() {
            this.items = data.sort(this.compareOnKey("name", "asc"));
            this.showPages();
        },
        compareOnKey(key, rule) {
            return function (a, b) {
                if (key === "name" || key === "job" || key === "country") {
                    let comparison = 0;
                    const fieldA = a[key].toUpperCase();
                    const fieldB = b[key].toUpperCase();
                    if (rule === "asc") {
                        if (fieldA > fieldB) {
                            comparison = 1;
                        } else if (fieldA < fieldB) {
                            comparison = -1;
                        }
                    } else {
                        if (fieldA < fieldB) {
                            comparison = 1;
                        } else if (fieldA > fieldB) {
                            comparison = -1;
                        }
                    }
                    return comparison;
                } else {
                    if (rule === "asc") {
                        return a.year - b.year;
                    } else {
                        return b.year - a.year;
                    }
                }
            };
        },
        checkView(index) {
            return index > this.pagination.to || index < this.pagination.from
                ? false
                : true;
        },
        checkPage(item) {
            if (item <= this.currentPage + 3) {
                return true;
            }
            return false;
        },
        search(value) {
            if (value.length > 1) {
                const options = {
                    shouldSort: true,
                    keys: ["name", "job"],
                    threshold: 0,
                };
                const fuse = new Fuse(data, options);
                this.items = fuse.search(value).map((elem) => elem.item);
            } else {
                this.items = data;
            }
            // console.log(this.items.length)

            this.changePage(1);
            this.showPages();
        },
        sort(field, rule) {
            this.items = this.items.sort(this.compareOnKey(field, rule));
            this.sorted.field = field;
            this.sorted.rule = rule;
        },
        changePage(page) {
            if (page >= 1 && page <= this.pagination.lastPage) {
                this.currentPage = page;
                const total = this.items.length;
                const lastPage = Math.ceil(total / this.view) || 1;
                const from = (page - 1) * this.view + 1;
                let to = page * this.view;
                if (page === lastPage) {
                    to = total;
                }
                this.pagination.total = total;
                this.pagination.lastPage = lastPage;
                this.pagination.perPage = this.view;
                this.pagination.currentPage = page;
                this.pagination.from = from;
                this.pagination.to = to;
                this.showPages();
            }
        },
        showPages() {
            const pages = [];
            let from = this.pagination.currentPage - Math.ceil(this.offset / 2);
            if (from < 1) {
                from = 1;
            }
            let to = from + this.offset - 1;
            if (to > this.pagination.lastPage) {
                to = this.pagination.lastPage;
            }
            while (from <= to) {
                pages.push(from);
                from++;
            }
            this.pages = pages;
        },
        changeView() {
            this.changePage(1);
            this.showPages();
        },
        isEmpty() {
            return this.pagination.total ? false : true;
        },
    };
};
