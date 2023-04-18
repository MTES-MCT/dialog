/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.scss';

// start the Stimulus application
import './bootstrap';

    const responsive = document.querySelector(".responsive");

     window.addEventListener("resize", () => {
        if (window.innerWidth<768) {
            responsive.classList.replace("fr-ml-3w","fr-mt-3w");
        }else{
            responsive.classList.replace("fr-mt-3w","fr-ml-3w");
        }
    });


