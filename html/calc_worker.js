var cacheName = "v1.02";
/** @var {Cache} */
var cache = null;

function initCache(event) {
    return caches.open(cacheName).then(function (openedCache) {
        cache = openedCache;
        return event;
    });
}

function installFunction() {
    return cache.addAll(['calc', 'mbn.min.js', 'favicon.ico', 'favicom.ico',
        'calc_style.css', 'calc_script.js', 'calc_manifest.json', 'calc_icon_1024.png']);
}

function fetchFunction(event) {
    var request = event.request;
    return cache.match(request).then(function (response) {
        if (response !== undefined && request.method === "GET") {
            return response;
        } else {
            return fetch(request).then(function (response) {
                return response;
            });
        }
    });
}

self.addEventListener('install', function (event) {
    event.waitUntil((cache !== null) ? installFunction(event) : initCache(event).then(installFunction));
});

self.addEventListener('fetch', function (event) {
    event.respondWith((cache !== null) ? fetchFunction(event) : initCache(event).then(fetchFunction));
});


//self.addEventListener('activate', function (event) {
//    event.waitUntil(clients.claim());
//});

self.addEventListener("message", function (event) {
    var respond = function (msg) {
        event.ports[0].postMessage(msg);
    };
    var data = event.data;

    if (data.command === "reloadCache") {
        if (cache === null) {
            respond({status: "ERR", message: "cache not initialized"});
        } else {
            cache.keys().then(function (requests) {
                var deletedRequests = 0;
                requests.forEach(function (request) {
                    cache.delete(request).then(function () {
                        if (requests.length === ++deletedRequests) {
                            installFunction().then(function () {
                                respond({status: "OK", message: "reload cache OK, " + deletedRequests + " items"});
                            }).catch(function () {
                                respond({status: "ERR", message: "reload cache error"});
                            })
                        }
                    })
                });
            });
        }
    }
});


