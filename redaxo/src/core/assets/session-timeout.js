/*
 * bootstrap-session-timeout
 * www.orangehilldev.com
 *
 * Copyright (c) 2014 Vedran Opacic
 * Licensed under the MIT license.
 */

if ('login' !== rex.page && rex.session_keep_alive) {
    (function ($) {
        /*jshint multistr: true */
        'use strict';
        $.sessionTimeout = function (options) {
            let defaults = {
                title: rex.i18n.session_timeout_title,
                message: rex.i18n.session_timeout_message,
                logoutButton: rex.i18n.session_timeout_logout_label,
                keepAliveButton: rex.i18n.session_timeout_refresh_label,
                keepAliveUrl: 'index.php?page=credits',
                ajaxType: 'POST',
                ajaxData: '',
                logoutUrl: rex.session_logout_url,

                keepAliveSession: rex.session_keep_alive * 1000, // stop request after x seconds - see config.yml
                keepAliveInterval: 5 * 1000, // * 60 * 1000, // 5 minutes
                keepAlive: true,

                onStart: false,
                onWarning: false,
                onLogout: false,
                countdownMessage: false,
                countdownBar: true,
                countdownSmart: true,

                sessionWarningAfter: (rex.session_keep_alive + rex.session_duration - rex.session_warning) * 1000, // - see config.yml
                sessionLogoutAfter: (rex.session_keep_alive + rex.session_duration) * 1000, // - see config.yml

                sessionMaxOverallDurationWarningAfter: (rex.session_starttime + rex.session_max_overall_duration - 2 * 60) * 1000, // - see config.yml
            };

            let opt = defaults,
                timer,
                countdown = {};

            // Extend user-set options over defaults
            if (options) {
                opt = $.extend(defaults, options);
            }
    console.log('keepAliveSession:' + opt.keepAliveSession);
    console.log('sessionWarningAfter:' + opt.sessionWarningAfter);
    console.log('sessionLogoutAfter:' + opt.sessionLogoutAfter);

            // Some error handling if options are miss-configured
            if (opt.sessionWarningAfter >= opt.sessionLogoutAfter) {
                console.error('session-timeout.js is miss-configured. Option "sessionLogoutAfter" must be equal or greater than "sessionWarningAfter".');
                return false;
            }

            // Unless user set his own callback function, prepare bootstrap modal elements and events
            if (typeof opt.onWarning !== 'function') {
                // If opt.countdownMessage is defined add a coundown timer message to the modal dialog
                let countdownMessage = opt.countdownMessage ?
                    '<p>' + opt.countdownMessage.replace(/{timer}/g, '<span class="countdown-holder"></span>') + '</p>' : '';

                let coundownBarHtml = opt.countdownBar ?
                    '<div class="progress"> \
                        <div class="progress-bar progress-bar-striped countdown-bar active" role="progressbar" style="min-width: 15px; width: 100%;"> \
                            <span class="countdown-holder"></span> \
                        </div> \
                    </div>' : '';
    console.log('Create dialog');
                // Create timeout warning dialog
                $('body').append(
                    '<div class="modal fade rex-session-timeout-dialog"> \
                        <div class="modal-dialog"> \
                            <div class="modal-content"> \
                                <div class="modal-header"> \
                                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button> \
                                    <h4 class="modal-title">' + opt.title + '</h4> \
                                </div> \
                                <div class="modal-body"> \
                                    <p>' + opt.message + '</p> \
                                    ' + countdownMessage + ' \
                                    ' + coundownBarHtml + ' \
                                </div> \
                                <div class="modal-footer"> \
                                    <button type="button" class="rex-session-timeout-dialog-logout btn btn-default">' + opt.logoutButton + '</button> \
                                    <button type="button" class="rex-session-timeout-dialog-keepalive btn btn-primary" data-dismiss="modal">' + opt.keepAliveButton + '</button> \
                                </div> \
                            </div> \
                        </div> \
                    </div>'
                );

                // "Logout" button click
                $('.rex-session-timeout-dialog-logout').on('click', function () {
                    window.location = opt.logoutUrl;
                });
                // "Stay Connected" button click
                $('.rex-session-timeout-dialog').on('hide.bs.modal', function () {
                    $.ajax(opt.keepAliveUrl, {
                        cache: false
                    });
                    // Restart session timer
    console.log('Hide Dialog');
                    startSessionTimer();
                });
            }

            // Keeps the server side connection live, by pingin url set in keepAliveUrl option.
            // KeepAlivePinged is a helper var to ensure the functionality of the keepAliveInterval option
            let keepAlivePinged = false;

            function keepAlive() {
                let keepAliveInterval = setInterval(function () {
                    $.ajax(opt.keepAliveUrl, {
                        cache: false
                    });
    console.log('Ping keep alive url');
                }, opt.keepAliveInterval);
                setTimeout(function () {
                    clearInterval(keepAliveInterval);
                }, opt.keepAliveSession);
            }

            function startSessionTimer() {
                // Clear session timer
                clearTimeout(timer);
                if (opt.countdownMessage || opt.countdownBar) {
                    startCountdownTimer('session', true);
                }

                if (typeof opt.onStart === 'function') {
                    opt.onStart(opt);
                }

                // If keepAlive option is set to "true", ping the "keepAliveUrl" url
                if (opt.keepAlive) {
                    keepAlive();
                }

                // Set session timer
                timer = setTimeout(function () {
                    // Check for onWarning callback function and if there is none, launch dialog
                    if (typeof opt.onWarning !== 'function') {
    console.log('Show Dialog');
                        $('.rex-session-timeout-dialog').modal('show');
                    } else {
                        opt.onWarning(opt);
                    }
                    // Start dialog timer
                    startDialogTimer();
                }, opt.sessionWarningAfter);
            }

            function startDialogTimer() {
                // Clear session timer
                clearTimeout(timer);
                if (!$('.rex-session-timeout-dialog').hasClass('in') && (opt.countdownMessage || opt.countdownBar)) {
                    // If warning dialog is not already open and either opt.countdownMessage
                    // or opt.countdownBar are set start countdown
                    startCountdownTimer('dialog', true);
                }
                // Set dialog timer
                timer = setTimeout(function () {
                    // Check for onLogout callback function and if there is none, launch redirect
                    if (typeof opt.onLogout !== 'function') {
    console.log('Logout');
                        // window.location = opt.logoutUrl;
                    } else {
                        opt.onLogout(opt);
                    }
                }, (opt.sessionLogoutAfter - opt.sessionWarningAfter));
            }

            function startCountdownTimer(type, reset) {
                // Clear countdown timer
                clearTimeout(countdown.timer);

                if (type === 'dialog' && reset) {
                    // If triggered by startDialogTimer start warning countdown
                    countdown.timeLeft = Math.floor((opt.sessionLogoutAfter - opt.sessionWarningAfter) / 1000);
                } else if (type === 'session' && reset) {
                    // If triggered by startSessionTimer start full countdown
                    // (this is needed if user doesn't close the warning dialog)
                    countdown.timeLeft = Math.floor(opt.sessionLogoutAfter / 1000);
                }
                // If opt.countdownBar is true, calculate remaining time percentage
                if (opt.countdownBar && type === 'dialog') {
                    countdown.percentLeft = Math.floor(countdown.timeLeft / ((opt.sessionLogoutAfter - opt.sessionWarningAfter) / 1000) * 100);
                } else if (opt.countdownBar && type === 'session') {
                    countdown.percentLeft = Math.floor(countdown.timeLeft / (opt.sessionLogoutAfter / 1000) * 100);
                }
                // Set countdown message time value
                let countdownEl = $('.countdown-holder');
                let secondsLeft = countdown.timeLeft >= 0 ? countdown.timeLeft : 0;
                if (opt.countdownSmart) {
                    let minLeft = Math.floor(secondsLeft / 60);
                    let secRemain = secondsLeft % 60;
                    let countTxt = minLeft > 0 ? minLeft + 'm' : '';
                    if (countTxt.length > 0) {
                        countTxt += ' ';
                    }
                    countTxt += secRemain + 's';
                    countdownEl.text(countTxt);
                } else {
                    countdownEl.text(secondsLeft + 's');
                }

                // Set countdown message time value
                if (opt.countdownBar) {
                    $('.countdown-bar').css('width', countdown.percentLeft + '%');
                }

                // Countdown by one second
                countdown.timeLeft = countdown.timeLeft - 1;
                countdown.timer = setTimeout(function () {
                    // Call self after one second
                    startCountdownTimer(type);
                }, 1000);
            }

            // Start session timer
            startSessionTimer();

        };
    })(jQuery);

    $(document).on('rex:ready', function () {
        $.sessionTimeout();
    });
}
