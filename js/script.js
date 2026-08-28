/**
 * MUTUKA.COM — js/script.js
 * Interactions dynamiques : mode sombre, menu mobile, aperçu photo, calculatrice de location,
 * modales, onglets de compte et galerie photo.
 */

document.addEventListener("DOMContentLoaded", () => {
    // 0. Gestion du Mode Sombre (Dark Mode)
    const themeBtn = document.getElementById("theme-toggle-btn");
    const mobileThemeBtn = document.getElementById("mobile-theme-toggle");

    function updateThemeIcons(isDark) {
        document.querySelectorAll(".theme-toggle").forEach((btn) => {
            const sunIcon = btn.querySelector(".sun-icon");
            const moonIcon = btn.querySelector(".moon-icon");
            if (sunIcon && moonIcon) {
                if (isDark) {
                    sunIcon.style.display = "block";
                    moonIcon.style.display = "none";
                } else {
                    sunIcon.style.display = "none";
                    moonIcon.style.display = "block";
                }
            }
        });
    }

    function toggleTheme() {
        const currentTheme = document.documentElement.getAttribute("data-theme") || "light";
        const newTheme = (currentTheme === "dark") ? "light" : "dark";
        document.documentElement.setAttribute("data-theme", newTheme);
        localStorage.setItem("mutuka_theme", newTheme);
        updateThemeIcons(newTheme === "dark");
    }

    // Initialiser les icônes selon le thème actuel
    const initialTheme = document.documentElement.getAttribute("data-theme") || "light";
    updateThemeIcons(initialTheme === "dark");

    if (themeBtn) themeBtn.addEventListener("click", toggleTheme);
    if (mobileThemeBtn) mobileThemeBtn.addEventListener("click", toggleTheme);

    // 1. Menu Mobile
    const burger = document.getElementById("burger");
    const mobileNav = document.getElementById("mobile-nav");
    if (burger && mobileNav) {
        burger.addEventListener("click", () => {
            const isOpen = mobileNav.classList.toggle("open");
            burger.setAttribute("aria-expanded", String(isOpen));
        });

        mobileNav.querySelectorAll(".nav-link").forEach((link) => {
            link.addEventListener("click", () => {
                mobileNav.classList.remove("open");
                burger.setAttribute("aria-expanded", "false");
            });
        });
    }

    // 2. Galerie photo détails (vignettes cliquables)
    const mainImg = document.getElementById("main-detail-img");
    const thumbs = document.querySelectorAll(".thumb-item");
    if (mainImg && thumbs.length > 0) {
        thumbs.forEach((thumb) => {
            thumb.addEventListener("click", () => {
                thumbs.forEach((t) => t.classList.remove("active"));
                thumb.classList.add("active");
                const newSrc = thumb.getAttribute("data-src");
                if (newSrc) {
                    mainImg.src = newSrc;
                }
            });
        });
    }

    // 3. Calculatrice en direct de location (dates -> jours -> montant total)
    const dateDebut = document.getElementById("rent_date_debut");
    const dateFin = document.getElementById("rent_date_fin");
    const pricePerDayInput = document.getElementById("rent_price_per_day");
    const previewDays = document.getElementById("preview_nb_days");
    const previewTotal = document.getElementById("preview_total_price");

    function calculateRental() {
        if (!dateDebut || !dateFin || !pricePerDayInput || !previewDays || !previewTotal) return;
        
        const startVal = dateDebut.value;
        const endVal = dateFin.value;
        const pricePerDay = parseFloat(pricePerDayInput.value) || 0;

        if (startVal && endVal) {
            const d1 = new Date(startVal);
            const d2 = new Date(endVal);
            const diffTime = d2.getTime() - d1.getTime();
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;

            if (diffDays > 0) {
                const total = diffDays * pricePerDay;
                previewDays.textContent = `${diffDays} jour${diffDays > 1 ? 's' : ''}`;
                previewTotal.textContent = new Intl.NumberFormat('fr-FR').format(total) + ' $';
            } else {
                previewDays.textContent = 'Dates invalides';
                previewTotal.textContent = '0 $';
            }
        }
    }

    if (dateDebut && dateFin) {
        dateDebut.addEventListener("change", () => {
            if (dateDebut.value && (!dateFin.value || dateFin.value < dateDebut.value)) {
                dateFin.min = dateDebut.value;
                dateFin.value = dateDebut.value;
            }
            calculateRental();
        });
        dateFin.addEventListener("change", calculateRental);
        calculateRental();
    }

    // 4. Modales de réservation / achat
    const modalButtons = document.querySelectorAll("[data-open-modal]");
    const modalCloses = document.querySelectorAll("[data-close-modal]");

    modalButtons.forEach((btn) => {
        btn.addEventListener("click", () => {
            const targetId = btn.getAttribute("data-open-modal");
            const modal = document.getElementById(targetId);
            if (modal) {
                modal.classList.add("open");
            }
        });
    });

    modalCloses.forEach((btn) => {
        btn.addEventListener("click", () => {
            const modal = btn.closest(".modal-backdrop");
            if (modal) {
                modal.classList.remove("open");
            }
        });
    });

    // Fermeture de modal au clic sur le fond
    document.querySelectorAll(".modal-backdrop").forEach((backdrop) => {
        backdrop.addEventListener("click", (e) => {
            if (e.target === backdrop) {
                backdrop.classList.remove("open");
            }
        });
    });

    // 5. Onglets de l'espace « Mon compte »
    const tabLinks = document.querySelectorAll(".tab-link");
    const tabContents = document.querySelectorAll(".tab-content");

    if (tabLinks.length > 0 && tabContents.length > 0) {
        const currentHash = window.location.hash.replace("#", "");
        let activeTabFound = false;

        tabLinks.forEach((link) => {
            const tabTarget = link.getAttribute("data-tab");
            if (currentHash && tabTarget === currentHash) {
                tabLinks.forEach((l) => l.classList.remove("active"));
                tabContents.forEach((c) => c.classList.remove("active"));
                link.classList.add("active");
                const targetContent = document.getElementById(`tab-${tabTarget}`);
                if (targetContent) targetContent.classList.add("active");
                activeTabFound = true;
            }

            link.addEventListener("click", () => {
                tabLinks.forEach((l) => l.classList.remove("active"));
                tabContents.forEach((c) => c.classList.remove("active"));

                link.classList.add("active");
                const targetId = link.getAttribute("data-tab");
                const targetContent = document.getElementById(`tab-${targetId}`);
                if (targetContent) {
                    targetContent.classList.add("active");
                    window.location.hash = targetId;
                }
            });
        });

        if (!activeTabFound && tabLinks[0]) {
            tabLinks[0].classList.add("active");
            if (tabContents[0]) tabContents[0].classList.add("active");
        }
    }

    // 6. Prévisualisation des images lors de la publication
    const fileInput = document.getElementById("vehicle_photos_input");
    const previewContainer = document.getElementById("photos_preview_container");

    if (fileInput && previewContainer) {
        fileInput.addEventListener("change", (e) => {
            previewContainer.innerHTML = "";
            const files = Array.from(e.target.files);

            files.forEach((file) => {
                if (file.type.startsWith("image/")) {
                    const reader = new FileReader();
                    reader.onload = (event) => {
                        const img = document.createElement("img");
                        img.src = event.target.result;
                        img.className = "preview-img";
                        previewContainer.appendChild(img);
                    };
                    reader.readAsDataURL(file);
                }
            });
        });
    }

    // 7. Toggle type d'annonce sur publier.php (Vente vs Location)
    const typeRadios = document.querySelectorAll("input[name='type_transaction']");
    const cautionGroup = document.getElementById("caution_group");
    const priceLabel = document.getElementById("price_label");
    const priceUnit = document.getElementById("price_unit");

    if (typeRadios.length > 0) {
        typeRadios.forEach((radio) => {
            radio.parentElement.addEventListener("click", () => {
                document.querySelectorAll(".type-option").forEach((opt) => opt.classList.remove("selected"));
                radio.parentElement.classList.add("selected");
                radio.checked = true;

                if (radio.value === "location") {
                    if (cautionGroup) cautionGroup.style.display = "block";
                    if (priceLabel) priceLabel.textContent = "Tarif journalier ($ / jour)";
                    if (priceUnit) priceUnit.textContent = "$ / jour";
                } else {
                    if (cautionGroup) cautionGroup.style.display = "none";
                    if (priceLabel) priceLabel.textContent = "Prix de vente ($)";
                    if (priceUnit) priceUnit.textContent = "$";
                }
            });
        });
    }
});
