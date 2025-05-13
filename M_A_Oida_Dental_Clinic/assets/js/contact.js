// assets/js/contact.js

// Notification dropdown
document.addEventListener("DOMContentLoaded", function () {
    const bellToggle = document.querySelector(".notification-toggle");
    const wrapper = document.querySelector(".notification-wrapper");

    bellToggle.addEventListener("click", function (e) {
        e.stopPropagation();
        wrapper.classList.toggle("show");
    });

    document.addEventListener("click", function (e) {
        if (!wrapper.contains(e.target)) {
            wrapper.classList.remove("show");
        }
    });
});

// Page load effects & link highlighting
document.addEventListener("DOMContentLoaded", function () {
    const currentPage = window.location.pathname.split("/").pop();
    document.querySelectorAll(".nav-links a").forEach(link => {
        if (link.getAttribute("href") === currentPage) {
            link.classList.add("active");
        }
    });

    // Smooth transitions (now picks up <a class="book-now">)
    const pageLinks = document.querySelectorAll(".nav-links a, .book-now");
    pageLinks.forEach(el => {
        el.addEventListener("click", function (event) {
            event.preventDefault();
            const target = this.getAttribute("href");
            document.body.style.opacity = "0";
            setTimeout(() => {
                window.location.href = target;
            }, 300);
        });
    });

    // Smooth scrolling for anchors
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener("click", function (e) {
            e.preventDefault();
            document.getElementById(this.getAttribute("href").slice(1))
                    .scrollIntoView({ behavior: "smooth" });
        });
    });

    // Fade-in
    document.body.style.opacity = "0";
    setTimeout(() => document.body.style.opacity = "1", 300);
});

// Branch & map loader
const branches = {
    "north-fairview": {
      img:    "assets/photos/regalado_branch.png",
      mapUrl: "https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3668.0860597628844!2d121.0596292799073!3d14.711035491402756!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397b10d18e2494d%3A0x7ad9da87e3339a6d!2sM%20and%20A%20Oida%20Dental%20Clinic!5e1!3m2!1sen!2sph!4v1746116763981!5m2!1sen!2sph"
    }
};

document.addEventListener("DOMContentLoaded", () => {
    const key = "north-fairview";
    const imgEl = document.querySelector(".contact-image");
    const mapIframe = document.getElementById("googleMap");
    if (branches[key]) {
        if (imgEl) imgEl.src = branches[key].img;
        if (mapIframe) mapIframe.src = branches[key].mapUrl;
    }
});
