function readURL(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function (e) {
            var img = document.getElementById('preview_image');
            img.src = e.target.result;
            img.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

var imgInp = document.getElementById('imgInp');
if (imgInp) {
    imgInp.addEventListener('change', function () {
        readURL(this);
    });
}
