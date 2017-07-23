(function(){
    var self = lightning.social = {
        google: {
            signin: function() {
                lightning.require('https://apis.google.com/js/platform.js', function(){
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
                                        self.signinComplete('google', 'auth', JSON.stringify(token));
                                        return;
                                    }
                                }
                            } else {
                                self.signinComplete('google', 'id', token_data.getAuthResponse().id_token);
                            }
                        });
                    });
                });
            },
            signout: function() {
                lightning.require('https://apis.google.com/js/platform.js', function() {
                    gapi.load('auth2', function () {
                        var auth2 = gapi.auth2.getAuthInstance() || gapi.auth2.init({
                                client_id: lightning.vars.social.google.client_id,
                            });
                        self.google.finalizeLogout();
                    });
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
                        self.google.finalizeLogout();
                    }
                }, 500);
            }
        },
        facebook: {
            init: function() {
                FB.init({
                    appId      : lightning.vars.social.facebook.appid,
                    cookie     : true,  // enable cookies to allow the server to access
                                        // the session
                    xfbml      : false,  // parse social plugins on this page
                    version    : 'v2.2' // use version 2.2
                });
            },

            signin: function() {
                if (!lightning.vars.social.facebook.appid) {
                    console.log('Missing facebook app ID');
                    return;
                }
                self.facebook.init();

                var settings = {};
                var scope = lightning.get('social.facebook.scope');
                if (scope) {
                    settings.scope = scope;
                }

                FB.login(self.facebook.signinComplete, settings);
            },

            // This is called with the results from from FB.getLoginStatus().
            signinComplete: function(response) {
                if (response.status === 'connected') {
                    if (lightning.vars.social.authorize) {
                        self.signinComplete('facebook', 'auth', response.authResponse.accessToken);
                    } else {
                        self.signinComplete('facebook', 'id', response.authResponse.signedRequest);
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
            $('.social-share').on('click', 'div', self.shareClick);
        },
        shareClick: function() {
            var el = $(this);
            var url = el.closest('.social-share').data('url');
            if (el.is('.facebook')) {
                self.sharePop('http://www.facebook.com/sharer.php?u=' + url);
            } else if (el.is('.twitter')) {
                self.sharePop('https://twitter.com/intent/tweet?url=' + url);
            } else if (el.is('.google')) {
                self.sharePop('https://plus.google.com/share?url=' + url);
            } else if (el.is('.linkedin')) {
                self.sharePop('http://www.linkedin.com/shareArticle?mini=true&url=' + url);
            } else if (el.is('.pinterest')) {
                self.sharePop('http://pinterest.com/pin/create/button/?url=' + url
                    + '&media=' + encodeURIComponent($('meta[property="og:image"]').attr('content'))
                    + '&description=' + encodeURIComponent($('meta[property="og:description"]').attr('content')));
            }
        },
        sharePop: function(url) {
            var winHeight = 350;
            var winWidth = 520;
            var winTop = (screen.height / 2) - (winHeight / 2);
            var winLeft = (screen.width / 2) - (winWidth / 2);
            window.open(url, 'sharer', 'top=' + winTop + ',left=' + winLeft + ',toolbar=0,status=0,width=' + winWidth + ',height=' + winHeight);
        },

        initLogout: function(site) {
            $('.logout_button').on('click', function(event){
                event.preventDefault();
                self[site].signout();
            });
        },
        initLogin: function() {
            $('.social-signin').on('click', function(){
                if ($(this).is('.google')) {
                    self.google.signin();
                }
                if ($(this).is('.facebook')) {
                    self.facebook.signin();
                }
                if ($(this).is('.twitter')) {
                    self.twitter.signin();
                }
            });
        },
        signinComplete: function(site, type, token) {
            var signinLocation = lightning.get('social.signin_url');
            if (signinLocation == null) {
                signinLocation = '/user';
            }
            var form = $('<form method="post" style="display:none">').attr('action', signinLocation);
            form.append('<input type="hidden" name="token" value="' + lightning.vars.token + '">');
            var redirect = lightning.get('social.login_redirect');
            if (redirect) {
                form.append('<input type="hidden" name="redirect" value="' + redirect + '">');
            }
            form.append('<input type="hidden" name="action" value="' + site + '-login">');
            form.append($('<input type="hidden" name="' + type + '-token">').val(token));
            $(document.body).append(form);
            form.submit();
        }
    };
})();
