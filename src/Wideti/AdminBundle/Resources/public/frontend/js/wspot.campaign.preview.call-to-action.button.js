function translate(orientation, prefix) {
    if (orientation === 'horizontal') {
        switch (prefix) {
            case "flex-start":
                return "left";
            case "center":
                return "center";
            case "flex-end":
                return "right";
        }
    }

    if (orientation === 'vertical') {
        switch (prefix) {
            case "flex-start":
                return "top";
            case "center":
                return "middle";
            case "flex-end":
                return "bottom";
        }
    }

}

function getSizes(img, button) {
    return {
        imgWidth: img.clientWidth,
        imgHeight: img.clientHeight,
        divWidth: $('.backstretch-item').width(),
        divHeight: $('.backstretch-item').height(),
        buttonHeight: button.height(),
        buttonWidth: button.width(),
    }
}

// Vertical Functions
function alignTop(sizes, buttom) {
    button.css("top", "0px");
    console.log("alignTop()");
}

function alignMiddle(sizes, button) {
    var middlePoint = (sizes.imgHeight / 2) - sizes.buttonHeight;
    button.css("top", middlePoint + "px")
    console.log("alignMiddle()");
}

function alignBottom(sizes, button) {
    button.css("bottom", "0px")
    console.log("alignBottom()");
}

// Horizontal Functions
function alignLeft(sizes, button) {
    var sideMove = (sizes.divWidth - sizes.imgWidth) / 2;
    button.css("left",  sideMove + "px");
    console.log("alignLeft()");
}

function alignCenter(sizes, button) {
    var marginSize = 5;
    var sideMove = (sizes.divWidth - sizes.imgWidth) / 2;
    var centerPoint = ((sizes.imgWidth / 2) + sideMove) - (sizes.buttonWidth / 2);
    button.css("left", centerPoint + "px");
    console.log("alignCenter()");
}

function alignRight(sizes, button) {
    var sideMove = (sizes.divWidth - sizes.imgWidth) / 2;
    button.css("right",  sideMove + "px");
    console.log("alignRight()");
}