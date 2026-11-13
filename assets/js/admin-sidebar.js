
(function () {
    const eventMenuToggle = document.getElementById("eventMenuToggle");
    const eventSubmenu = document.getElementById("eventSubmenu");

    if (!eventMenuToggle || !eventSubmenu) {
        return;
    }

    // Kiem tra trang hien tai
    const currentPath = window.location.pathname;
    const submenuLinks = eventSubmenu.querySelectorAll("a");

    let activePage = false;

    submenuLinks.forEach(function (link) {
        const linkPath = new URL(link.href).pathname;

        if (linkPath === currentPath) {
            link.classList.add("active");
            activePage = true;
        }
    });

    // Tu dong mo menu neu dang o trang con
    if (activePage) {
        eventSubmenu.hidden = false;
        eventMenuToggle.setAttribute("aria-expanded", "true");
    }

    // Dong va mo menu khi nhan nut
    eventMenuToggle.addEventListener("click", function () {
        const isExpanded =
            eventMenuToggle.getAttribute("aria-expanded") === "true";

        eventMenuToggle.setAttribute(
            "aria-expanded",
            String(!isExpanded)
        );

        eventSubmenu.hidden = isExpanded;
    });
})();
