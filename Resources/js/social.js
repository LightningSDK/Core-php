lightning.social = {
    google: {
        signin: function() {
            if (typeof gapi == 'undefined') {
                $('body').append($('<script src="https://apis.google.com/js/platform.js"></script>'));
                setTimeout(lightning.social.google.signin, 500);
                return;
            }
            gapi.load('auth2', function() {
                var auth2 = gapi.auth2.getAuthInstance() || gapi.auth2.init({
                        client_id: lightning.vars.social.google.client_id,
                    });
                response = auth2.signIn().then(function(token_data){
                    if (lightning.vars.social.authorize) {
                        for (var i in token_data) {
                            if (typeof token_data[i] == 'object' && token_data[i].hasOwnProperty('access_token')) {
                                var token = {
                                    access_token: token_data[i].access_token,
                                    created: token_data[i].first_issued_at,
                                    expires_in: token_data[i].expires_in,
                                };
                                lightning.social.signinComplete('google', 'auth', JSON.stringify(token));
                                return;
                            }
                        }
                    } else {
                        lightning.social.signinComplete('google', 'id', token_data.getAuthResponse().id_token);
                    }
                });
            });
        },
        signout: function() {
            if (typeof gapi == 'undefined') {
                $('body').append($('<script src="https://apis.google.com/js/platform.js"></script>'));
                setTimeout(lightning.social.google.signout, 500);
                return;
            }
            gapi.load('auth2', function() {
                var auth2 = gapi.auth2.getAuthInstance() || gapi.auth2.init({
                        client_id: lightning.vars.social.google.client_id,
                    });
                lightning.social.google.finalizeLogout();
            });
        },
        finalizeLogout: function() {
            var auth2 = gapi.auth2.getAuthInstance();
            var loggedOut = false;
            auth2.signOut().then(function () {
                loggedOut = true;
                window.location.href = '/user?action=logout';
            });
            setTimeout(function(){
                if (!loggedOut) {
                    lightning.social.google.finalizeLogout();
                }
            }, 500);
        }
    },
    facebook: {
        init: function() {
        },

        signin: function() {
            window.fbAsyncInit = function() {
                FB.init({
                    appId      : lightning.vars.social.facebook.appid,
                    cookie     : true,  // enable cookies to allow the server to access
                                        // the session
                    xfbml      : false,  // parse social plugins on this page
                    version    : 'v2.2' // use version 2.2
                });

                FB.login(lightning.social.facebook.signinComplete);
            };

            (function(d, s, id) {
                var js, fjs = d.getElementsByTagName(s)[0];
                if (d.getElementById(id)) return;
                js = d.createElement(s); js.id = id;
                js.src = "//connect.facebook.net/en_US/sdk.js";
                fjs.parentNode.insertBefore(js, fjs);
            }(document, 'script', 'facebook-jssdk'));
        },

        // This is called with the results from from FB.getLoginStatus().
        signinComplete: function(response) {
            if (response.status === 'connected') {
                if (lightning.vars.social.authorize) {
                    lightning.social.signinComplete('facebook', 'auth', response.authResponse.accessToken);
                } else {
                    lightning.social.signinComplete('facebook', 'id', response.authResponse.signedRequest);
                }
            } else {
                console.log('could not sign in to facebook');
            }
        },

        signout: function() {

        }
    },
    twitter: {
        signin: function() {
            window.location.href = lightning.vars.social.twitter.signin_url;
        }
    },

    initShare: function() {
        $('.social-share').on('click', 'div', lightning.social.shareClick);
    },
    shareClick: function() {
        var el = $(this);
        var url = el.closest('.social-share').data('url');
        var winHeight = 350;
        var winWidth = 520;
        var winTop = (screen.height / 2) - (winHeight / 2);
        var winLeft = (screen.width / 2) - (winWidth / 2);
        if (el.is('.facebook')) {
            window.open('http://www.facebook.com/sharer.php?s=100&p[url]=' + url, 'sharer', 'top=' + winTop + ',left=' + winLeft + ',toolbar=0,status=0,width=' + winWidth + ',height=' + winHeight);
        } else if (el.is('.twitter')) {
            window.open('https://twitter.com/intent/tweet?url=' + url + '&via=' + lightning.vars.social.twitter.url + '', 'sharer', 'top=' + winTop + ',left=' + winLeft + ',toolbar=0,status=0,width=' + winWidth + ',height=' + winHeight);
        } else if (el.is('.google')) {
            window.open('https://plus.google.com/share?url=' + url, 'sharer', 'top=' + winTop + ',left=' + winLeft + ',toolbar=0,status=0,width=' + winWidth + ',height=' + winHeight);
        }
    },

    initLogout: function(site) {
        $('.logout_button').click(function(event){
            event.preventDefault();
            lightning.social[site].signout();
        });
    },
    initLogin: function() {
        $('.social-signin').click(function(){
            if ($(this).is('.google')) {
                lightning.social.google.signin();
            }
            if ($(this).is('.facebook')) {
                lightning.social.facebook.signin();
            }
            if ($(this).is('.twitter')) {
                lightning.social.twitter.signin();
            }
        });
    },
    signinComplete: function(site, type, token) {
        var form = $('<form action="/user" method="post" style="display:none">');
        form.append('<input type="hidden" name="token" value="' + lightning.vars.token + '">');
        form.append('<input type="hidden" name="action" value="' + site + '-login">');
        form.append($('<input type="hidden" name="' + type + '-token">').val(token));
        form.submit();
    }
};
