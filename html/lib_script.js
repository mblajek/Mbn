//legacy write and write evaluated
function w(a, c) {
    if (a === undefined) {
        a = "";
    } else if (a instanceof Array) {
        a = a.slice();
        var al = a.length;
        if (c === 'mono') {
            for (var i = 0; i < al; i++) {
                a[i] = "<span class=\"lb\"></span>" + a[i];
            }
        }
        a = a.join("<br>");
    } else if (c === 'mono') {
        a = "<span class=\"lb\"></span>" + a;
    }
    a = a.replace(/((\()|(, ))(modify)(\))/g, "$2<span class=\"it\">$3$4</span>$5");
    var id = false;
    if (c === "title2") {
        var title = a.replace(/<.*/, "");
        id = title.toLowerCase().replace(/[^0-9a-z]/g, "_");
        id = id.replace(/_+/g, "_");
    }
    document.write("<div" + (c ? (" class=\"" + c + "\"") : "") + (id ? (" id=\"" + id + "\"") : "") + ">" + a + "</div>");
}

function we(a) {
    w(a, "mono");
    try {
        var acode = (a instanceof Array) ? a.join("\n") : a;
        var e = eval(acode);
        w(String(e), "result");
        w(typeof e, "label");
    } catch (er) {
        w(String(er), "result");
        w("error", "label");
    }
}

window.addEventListener("load", function (ev) {
    document.getElementById("darkMode").addEventListener("click", function () {
        var dark = document.body.classList.toggle("dark");
        if (localStorage) {
            localStorage.setItem("dark", String(Number(dark)));
        }
    });
    if (localStorage && localStorage.getItem("dark") === "1") {
        document.body.classList.add("dark");
    }

    function showRelease(passedTest) {
        releaseStatus["passed" + passedTest] = true;
        if (releaseStatus.hashChanged && releaseStatus.passedPHP && releaseStatus.passedJS) {
            var releaseBtn = document.getElementById("releaseBtn");
            releaseBtn.style.visibility = "visible";
            releaseBtn.onclick = function () {
                releaseBtn.onclick = function () {
                };
                releaseBtn.style.color = "gray";
                var xmlhttp = new XMLHttpRequest();
                xmlhttp.onreadystatechange = function () {
                    if (xmlhttp.readyState === 4) {
                        alert(xmlhttp.responseText);
                        location.reload();
                    }
                };
                xmlhttp.open("POST", "mbn_release", true);
                xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                xmlhttp.send("");
            };
        }
    }

    displayTestStatus = function (lng, result) {
        var resultSpan = document.getElementById("result" + lng);
        try {
            var res = JSON.parse(result);
            var c = res.cache ? ", from cache" : "";
            var txt = lng + " v" + res.MbnV + ": " + res.status
               + " (" + res.count + " tests, " + res.time + " ms" + c + ")";
            for (var i = 0; i < res.errors.length; i++) {
                var error = res.errors[i];
                txt += "\n\n" + error.id + ") " + error.code + "\n!) " + error.correct + "\n=) " + error.incorrect;
            }
            resultSpan.innerText = txt;
            if (res.status === "OK") {
                showRelease(lng);
            }
        } catch (ex) {
            resultSpan.innerText = result;
        }
    }

    setTimeout(function () {
        var js = document.createElement("script");
        js.src = "mbn_test?js";
        document.body.appendChild(js);
    }, 100);
});


