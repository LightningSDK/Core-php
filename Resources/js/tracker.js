(function() {
    var self = lightning.tracker = {
        events: {
            pageView: {
                ga: 'pageview',
                fb: 'PageView'
            },
            viewContent: {
                fb: 'ViewContent',
            },
            search: {
                fb: 'Search',
            },
            addToCart: {
                fb: 'AddToCart',
                ga: 'event',
                category: 'Store',
            },
            addToWishlist: {
                fb: 'AddToWishlist',
                ga: 'event',
                category: 'Store',
            },
            initiateCheckout: {
                fb: 'InitiateCheckout',
                ga: 'event',
                category: 'Store',
            },
            addPaymentInfo: {
                fb: 'AddPaymentInfo',
                ga: 'event',
                category: 'Store',
            },
            purchase: {
                fb: 'Purchase',
                ga: 'event',
                category: 'Store',
            },
            optin: {
                fb: 'Lead',
                ga: 'event',
                category: 'User',
                action: 'optin',
            },
            register: {
                fb: 'CompleteRegistration',
                ga: 'event',
                category: 'User',
                action: 'register',
            },
            splitTest: {
                ga: 'event',
                label: 'Split Test',
            }
        },

        /**
         * Load the tracking scripts
         */
        init: function () {
            var scripts = [];
            if (lightning.vars.google_analytics_id) {
                scripts.push('//www.google-analytics.com/analytics.js');
            }
            if (lightning.vars.facebook_pixel_id) {
                n = window.fbq = function(){
                    n.callMethod ? n.callMethod.apply(n,arguments) : n.queue.push(arguments)
                };
                if(!window._fbq) {
                    window._fbq = n;
                }
                n.push = n;
                n.loaded = true;
                n.version = '2.0';
                n.queue=[];
                scripts.push('//connect.facebook.net/en_US/fbevents.js');
            }
            if (scripts.length > 0) {
                lightning.require(scripts, function () {
                    // Init the trackers
                    if (lightning.vars.google_analytics_id) {
                        ga('create', lightning.vars.google_analytics_id, 'auto');
                    }
                    if (lightning.vars.facebook_pixel_id) {
                        fbq('init', lightning.vars.facebook_pixel_id);
                    }

                    // Track the pageview
                    self.track(self.events.pageView);

                    // Track the split tests
                    var splitTests = lightning.get('splitTest');
                    if (splitTests) {
                        for (var i in splitTests) {
                            self.track(self.events.splitTest, {
                                category: i,
                                action: lightning.vars.splitTest[i],
                            });
                        }
                    }
                });
            }
        },

        /**
         * Send tracking data.
         *
         * Category and Action are required by GA.
         */
        track: function (eventType, data) {
            var trackingData = {};
            $.extend(trackingData, eventType, data);

            if (lightning.vars.google_analytics_id && trackingData.hasOwnProperty('ga')) {
                ga('send', trackingData.ga,
                    trackingData.category ? trackingData.category : undefined,
                    trackingData.action ? trackingData.action : undefined,
                    trackingData.label ? trackingData.label : undefined,
                    trackingData.value ? trackingData.value : undefined
                );
            }
            if (lightning.vars.facebook_pixel_id && trackingData.hasOwnProperty('fb')) {
                fbq('track', trackingData.fb);
            }
        }
    };
})();
