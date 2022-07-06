var mbnChange = function () {
};
window.addEventListener("load", function () {
    var elements = {
        inputField: document.getElementById("inputField"),
        outputField: document.getElementById("outputField"),
        timeOutput: document.getElementById("timeOutput"),
        mbnP: document.getElementById("mbnP"),
        mbnST: document.getElementById("mbnST"),
        reloadAll: document.getElementById("reloadAll")
    };
    elements.inputInitialHeight = elements.inputField.clientHeight;
    elements.inputStyle = window.getComputedStyle(elements.inputField, null);
    elements.inputHeightOffset = parseFloat(elements.inputStyle.borderTopWidth) + parseFloat(elements.inputStyle.borderBottomWidth);

    var delay = ("requestAnimationFrame" in window) ?
        (function (callback) {
            requestAnimationFrame(function () {
                requestAnimationFrame(callback)
            })
        }) : (function (callback) {
            setTimeout(callback, 60)
        });

    /* start, dots, print, error */
    var out = (function () {
        var timestamp = new (Mbn.extend({MbnP: 1, MbnT: true}))();
        var ret = {};
        var end = function () {
            if (timestamp.eq(0)) {
                return;
            }
            timestamp.add(Date.now(), true);
            if (timestamp.cmp(1000) === -1) {
                elements.timeOutput.textContent = timestamp + "ms";
            } else {
                timestamp.div(1000, true);
                if (timestamp.cmp(100) === 1) {
                    timestamp.round(true);
                }
                elements.timeOutput.textContent = timestamp + "s";
            }
            timestamp.set(0);
        };
        ret.print = function (text) {
            end();
            elements.outputField.style.color = "black";
            elements.outputField.value = String(text);
        };
        ret.error = function (text) {
            end();
            elements.outputField.style.color = "firebrick";
            elements.outputField.value = String(text);
        };
        ret.dots = function () {
            elements.outputField.style.color = "gray";
            elements.timeOutput.textContent = '...';
        };
        ret.start = function () {
            timestamp.set(-Date.now());
        };
        return ret;
    })();

    var MbnP = new (Mbn.extend(0))(2);
    var MbnSTs = ["0.0", "0._", "0,0", "0,_", "_.0", "_._", "_,0", "_,_"];
    var MbnST = MbnSTs[0];
    var MbnX = null;
    var getMbnX = function () {
        try {
            return Mbn.extend({
                MbnP: MbnP.toNumber(),
                MbnS: MbnST.charAt(1),
                MbnT: MbnST.charAt(2) === "_",
                MbnF: MbnST.charAt(0) === "_",
                MbnL: 1e4
            });
        } catch (e) {
            out.error(e);
            return null;
        }
    };

    var lastFullInput = null;

    var vars = {};
    elements.inputField.addEventListener("input", function () {
        elements.inputField.style.height = Math.ceil(elements.inputInitialHeight + elements.inputHeightOffset) + "px";
        elements.inputField.style.height = Math.ceil(elements.inputField.scrollHeight + elements.inputHeightOffset) + "px";
        var inputValue = elements.inputField.value;
        var currentFullInput = inputValue + "|" + MbnP + "|" + MbnST;
        if (MbnX !== null && lastFullInput !== currentFullInput) {
            out.dots();
            delay(function () {
                out.start();
                var result;
                try {
                    result = MbnX.calc(inputValue, vars);
                    out.print(result);
                } catch (e) {
                    out.error(e);
                }
            })
            lastFullInput = currentFullInput;
        }
    });
    var inputEvent = document.createEvent('Event');
    inputEvent.initEvent("input", false, false);
    mbnChange = function (pChange, formatChange) {
        var focusInput = true;
        if (formatChange === true) {
            MbnST = MbnSTs[(MbnSTs.indexOf(MbnST) + 1) % MbnSTs.length];
        } else {
            if (pChange) {
                MbnP.add(pChange, true);
            } else if (pChange === 0) {
                try {
                    focusInput = false;
                    MbnP.set(elements.mbnP.value);
                } catch (e) {
                }
            }
        }
        MbnP.max(0, true).min(999, true);
        MbnX = getMbnX();
        elements.mbnST.textContent = MbnST;
        if (elements.mbnP.value !== MbnP.toString()) {
            elements.mbnP.value = MbnP;
        }
        if (focusInput) {
            elements.inputField.focus();
        }
        elements.inputField.dispatchEvent(inputEvent);
    };
    mbnChange();

    if ('serviceWorker' in navigator) {
        var locationReload = function () {
            setTimeout(location.reload.bind(location), 250);
        }
        navigator.serviceWorker.register('calc_worker.js', {scope: "calc"})
            .catch(function (error) {
                console.error('Registration failed: ' + error);
            }).then(function (sw) {
            if (sw.active === null) {
                out.print("reloading 2/2")
                locationReload();
                return;
            }
            elements.reloadAll.addEventListener("click", function () {
                var mc = new MessageChannel();
                mc.port1.onmessage = function (event) {
                    sw.unregister().then(function () {
                        if (event.data.status === "OK") {
                            out.print("reloading 1/2")
                            locationReload();
                        } else {
                            out.error(event.data.message);
                        }
                    });
                };
                if (sw.active !== null) {
                    sw.active.postMessage({command: 'reloadCache'}, [mc.port2]);
                }
            });
        });
    } else {
        elements.reloadAll.disabled = true;
    }
    document.getElementById("newCalc").addEventListener("click", function () {
        window.open(location.href, 'w' + Date.now(), 'width=320,height=160,resizable=yes,toolbar=no,scrollbars=no');
    });
    document.getElementById("addOpt").addEventListener("click", function () {
        document.getElementById("addOpt").style.display = "none";
        document.getElementById("additionalOptions").style.display = "block";
    });
});
