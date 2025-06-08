<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.3/css/dataTables.bootstrap5.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
    crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.3/js/dataTables.bootstrap5.min.js"></script>

<link rel="stylesheet" href="assets/css/profile-icon.css">
<link rel="stylesheet" href="assets/css/notification.css">

<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap');

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: "Poppins", sans-serif;
    }

    body {
        background-color: white;
        color: #333;
    }

    /* Navigation Bar */
    .navbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 5px 20px;
        background-color: #8fbaf3;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        position: relative;
        top: 0;
        z-index: 1000;
    }

    /* Position the greeting away from the logo */
    .welcome-message {
        /* shift right; adjust as needed */
        font-size: 1.5rem;
        color: #333;
    }

    /* Bold only the user’s name */
    .welcome-message strong {
        font-weight: 700;
        color: black;
        /* optional accent color */
        margin-left: 4px;
        /* small gap after comma */
    }


    .logo {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        /* keep it round */
        overflow: hidden;
        /* hide anything outside the circle */
        object-fit: contain;
        /* scale the entire logo inside */
        background-color: white;
        /* optional: fill any empty space with white */
        display: block;
        /* ensure object-fit works */
        margin-left: 40px;
    }

    /* Make sure nav-links move to the right */
    .nav-links {
        margin-left: auto;
        display: flex;
        align-items: center;
    }

    .nav-links li {
        margin: 0 15px;
        list-style: none;
        /* This removes the bullet point */
    }

    /* Style for active link (clicked) */
    .nav-links a.active {
        color: #0d6efd;
        font-weight: 700;
    }

    .nav-links a {
        text-decoration: none;
        color: black;
        font-weight: 600;
        position: relative;
        padding: 5px 0;
        transition: color 0.3s;
    }

    /* the “underline” element, initially zero width */
    .nav-links a::after {
        content: "";
        position: absolute;
        left: 0;
        bottom: -2px;
        width: 0;
        height: 3px;
        background-color: #0d6efd;
        transition: width 0.3s ease;
    }

    .nav-links a:hover {
        color: #0d6efd;
    }

    /* expand on hover */
    .nav-links a:hover::after {
        width: 100%;
    }

    /* keep expanded on the link with .active */
    .nav-links a.active::after {
        width: 100%;
    }


    /* User Icon & Book Now Button */
    .nav-right {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .nav-right {
        gap: 20px;
        /* Reduce gap between elements */
    }

    .user-icon img.profile-pic {
        width: 50px;
        height: 50px;
        object-fit: cover;
        display: block !important;
        max-width: 100%;
        min-height: 40px;
    }

    .user-icon .fa-user {
        font-size: 1.2rem;
    }

    .user-icon:hover {
        border-color: #0d6efd;
        transform: scale(1.05);
        color: #0d6efd;
        /* Merge with existing hover */
    }

    .book-now {
        background-color: #124085;
        color: white;
        padding: 8px 16px;
        border-radius: 8px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: background 0.3s;
    }

    .book-now:hover {
        background-color: #0b5ed7;
    }

    footer {
        background-color: white;
        /* Dark background */
        color: #555;
        /* White text */
        font-weight: 350;
        font-size: 0.9rem;
        text-align: center;
        padding: 15px 0;
        font-size: 14px;
        font-family: "Poppins", sans-serif;
        bottom: 0;
        width: 100%;
    }



    /* Hero Section */
    .hero {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 5px;
        background-color: white;
        /* Light background for contrast */
        margin: 20px auto;
        max-width: 1200px;
        /* Center the content and limit width */
    }

    .hero-text {
        max-width: 50%;
        padding-right: 20px;
    }

    .hero-text h1 {
        font-size: 2.5rem;
        font-weight: bold;
        color: #6b42a5;
        margin-bottom: 20px;
    }

    .hero-text h1 .highlight {
        color: #124085;
        /* Purple highlight */
    }

    .hero-text p {
        font-size: 1.2rem;
        color: #124085;
        font-weight: 500;
        margin-bottom: 30px;
    }

    .hero-text p .italic {
        font-style: italic;
        color: #6b42a5;
        /* Purple for emphasis */
    }

    .hero-text .intro-text {
        font-size: 1.0rem;
        color: black;
        font-weight: 500;
        line-height: 1.6;
        margin: 20px 0;
        /* space above & below */
        margin-bottom: 30px;
        text-align: justify;
        /* this makes the text flush both left/right */
    }

    /* Hamburger toggle button */
    .nav-toggle {
        display: none;
    }

    .nav-toggle-label {
        display: none;
        position: absolute;
        top: 1rem;
        right: 1.5rem;
        height: 2rem;
        width: 2rem;
        cursor: pointer;
        z-index: 1100;
    }

    .nav-toggle-label span,
    .nav-toggle-label span::before,
    .nav-toggle-label span::after {
        display: block;
        background: black;
        height: 3px;
        width: 100%;
        border-radius: 2px;
        position: absolute;
        transition: all 0.3s;
    }

    .nav-toggle-label span {
        top: 50%;
        transform: translateY(-50%);
    }

    .nav-toggle-label span::before {
        content: '';
        top: -8px;
    }

    .nav-toggle-label span::after {
        content: '';
        top: 8px;
    }

    /* mobile menu hidden by default */
    .nav-menu {
        display: flex;
        align-items: center;
    }

    @media (max-width: 1200px) {
        .nav-toggle-label {
            display: block;
        }

        .nav-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background: #8fbaf3;
            flex-direction: column;
            width: 100%;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            z-index: 1000;
        }

        .nav-toggle:checked+.nav-toggle-label span {
            background: transparent;
        }

        .nav-toggle:checked+.nav-toggle-label span::before {
            transform: rotate(45deg) translate(5px, 5px);
        }

        .nav-toggle:checked+.nav-toggle-label span::after {
            transform: rotate(-45deg) translate(5px, -5px);
        }

        .nav-toggle:checked~.nav-menu {
            max-height: 500px;
            /* enough to show all items */
        }

        .nav-links {
            flex-direction: column;
            margin: 0;
            padding: 1rem 0;
            gap: 20px;
        }

        .nav-links li {
            margin: 0;
        }

        .nav-right {
            flex-direction: column;
            padding-bottom: 1rem;
        }
    }

    /* Hero intro-text center and responsive */
    @media (max-width: 768px) {
        .hero-text .intro-text {
            text-align: justify;
            margin: 0 auto 30px;
            max-width: 90%;
        }

        .hero-text .features {
            justify-content: center;
        }
    }

    /* Image grid 4 columns on phone/tablet */
    @media (max-width: 1024px) {
        .image-container {
            grid-template-columns: repeat(4, 1fr);
            grid-auto-rows: auto;
            padding: 0 20px;
        }
    }

    /* Responsive adjustments for hero section and overall layout */
    @media (max-width: 768px) {

        /* Stack hero content vertically */
        .hero {
            flex-direction: column;
            padding: 10px;
        }

        /* Full width for text block */
        .hero-text {
            max-width: 100%;
            padding: 0;
            margin-bottom: 20px;
        }

        /* Reduce heading sizes */
        .hero-text h1 {
            font-size: 2rem;
            text-align: center;
        }

        .hero-text p,
        .hero-text .intro-text {
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .hero-text p {
            text-align: center;
        }

        /* Center features list and adjust layout */
        .hero-text .features {
            justify-content: center;
            gap: 5px 20px;
            margin-bottom: 20px;

        }

        .hero-text .features li {
            flex: 1 1 100%;
            text-align: center;

        }

        .features li {
            text-align: justify;
            justify-content: center;
        }
    }

    @media (max-width: 480px) {

        /* Shrink padding and fonts for very small screens */
        .navbar {
            padding: 5px 10px;
        }

        .logo {
            margin-left: 10px;
            width: 50px;
            height: 50px;
        }

        .welcome-message {
            font-size: 1rem;
            margin: 5px 0;
        }

        .book-now {
            padding: 6px 12px;
            font-size: 0.9rem;
        }

        /* Hero heading smaller */
        .hero-text h1 {
            font-size: 1.5rem;
        }
    }

    @media (max-width: 480px) {
        .image-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            width: 100%;
            margin: 0;
            padding: 0;
        }

        .image-container img {
            width: 100%;
            /* fill its own cell */
            height: 100px;
            /* taller “fat” thumbnails */
            object-fit: cover;
            /* crop/scale to fill */
            border-radius: 6px;
            /* optional rounding */
            display: block;
        }
    }

    /* Tablet and below */
    @media (max-width: 768px) {
        body {
            /* make room for a fixed footer that’s 60px tall */
            padding-bottom: 60px;
        }

        footer {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 60px;
            /* match your padding-bottom */
            box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.1);
        }
    }

    /* Mobile only */
    @media (max-width: 480px) {
        body {
            padding-bottom: 70px;
            /* slightly taller if you want more breathing room */
        }

        footer {
            height: 70px;
            /* match your mobile padding-bottom */
        }
    }
</style>