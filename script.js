//Turutan Images
const sliderImages = [
    "images/homepage.png",
    "images/bpball.png",
    "images/sambaaa.png"
];

let currentIndex = 0;

const imgElement = document.querySelector(".slider-container img");
const nextBtn = document.querySelector(".btn-next");
const prevBtn = document.querySelector(".btn-prev");

function updateImage() {
    if (imgElement) {
        imgElement.src = sliderImages[currentIndex];
    }
}
//nextButton
if (nextBtn) {
    nextBtn.addEventListener("click", function() {
        currentIndex++;
        if (currentIndex >= sliderImages.length) {
            currentIndex = 0;
        }
        updateImage();
    });
}
//Previous Button
if (prevBtn) {
    prevBtn.addEventListener("click", function() {
        currentIndex--;
        if (currentIndex < 0) {
            currentIndex = sliderImages.length - 1;
        }
        updateImage();
    });
}


