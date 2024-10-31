var Poetica = (function() {

    var Poetica = function(data) {
        this.submitted = false;
        this.mediaFrame;
        jQuery.extend(this, data);

        jQuery(document).ready(this.onDocumentReady.bind(this));
        if(this.poeticaLocation && this.new_post) {
            jQuery(window).on('beforeunload', (function() {
                if (!this.saving) {
                    return tinyMCE.translate('You have unsaved changes are you sure you want to navigate away?');
                }
            }).bind(this));
        }
        jQuery(function() {
          // So we  can just toggle later
          jQuery('body').addClass('focus-off');
        });
    }
    
    Poetica.prototype.onDocumentReady = function() {
        this.setupMessageChannel();

        if (this.poeticaLocation) {
            jQuery('#poetica-tinymce').click(this.onTinyMCEClick.bind(this));
            jQuery('#poetica-tinymce-confirm').click(this.onTinyMCEConfirm.bind(this));
            jQuery('#poetica-tinymce-cancel').click(this.onTinyMCECancel.bind(this));
            jQuery('#save-post, #publish, #post-preview').click(this.onSavePublishPreview.bind(this));
            jQuery('#poetica-insert-media-button').click(this.onAddMedia.bind(this));
        } else {
            jQuery('#poetica-topoetica').click(this.onToPoeticaClick.bind(this));
        }
        jQuery('#all-poetica-tinymce-confirm').click(this.onAllTinyMCEConfirm.bind(this));
        jQuery('#poetica-group-link').click(this.onGroupLink.bind(this));
        jQuery('#poetica-user-link').click(this.onUserLink.bind(this));
        jQuery('#poetica-dfw').click(this.onFullScreen.bind(this));
        if(window.location.href.indexOf('plugins.php') > -1) {
          jQuery('#poetica .deactivate a').live('click', this.confirmDeactivate.bind(this));
        }
    }

    Poetica.prototype.setupMessageChannel = function() {
        this.eventCallbacks = {};
        jQuery(window).on('message', this.handleMessage.bind(this));
    }

    Poetica.prototype.handleMessage = function(e) {
        var messageEvent = e.originalEvent;
        if (jQuery.type(messageEvent.data) === 'string') {
            this.trigger(messageEvent.data);
        } else if (messageEvent.data && messageEvent.data.event) {
            var event = messageEvent.data.event;
            this.trigger(event, messageEvent.data);
        }
    }

    // Add pushMessage event listener
    // return false from the callback to remove the callback.
    Poetica.prototype.on = function(evt, cb) {
        var callbacks = this.eventCallbacks[evt] = this.eventCallbacks[evt] || [];
        callbacks.push(cb);
    }

    Poetica.prototype.trigger = function(evt, data) {
        var callbacks = this.eventCallbacks[evt] || [];
        callbackFunc = function (callback) { return callback(data) !== false; }
        this.eventCallbacks[evt] = callbacks.filter(callbackFunc);
    }

    Poetica.prototype.postMessage = function(message) {
        var iframe = jQuery('.poetica-iframe')[0];
        iframe.contentWindow.postMessage(message, this.docDomain);
    }

    Poetica.prototype.onSavePublishPreview = function(clickEvent) {
      this.saving = true;
    }

    Poetica.prototype.onAddMedia = function(event) {
        
      event.preventDefault();
      
      // If the media frame already exists, reopen it.
      if ( this.mediaFrame ) {
        this.mediaFrame.open();
        return;
      }
      
      // Create a new media frame
      this.mediaFrame = wp.media({
        title: 'Select media to add to your post',
        button: {
          text: 'Use this media'
        },
        multiple: false  // Set to true to allow multiple files to be selected
      });

      
      // When an image is selected in the media frame...
      this.mediaFrame.on( 'select', this.onMediaSelected.bind(this));

      // Finally, open the modal on click
      this.mediaFrame.open();
      return false;
    }

    Poetica.prototype.onMediaSelected = function(clickEvent) {
      
      var attachment = this.mediaFrame.state().get('selection').first().toJSON();
      this.postMessage({type:'addMedia', url: attachment.url})
      return false;
    };

    Poetica.prototype.onToPoeticaClick = function(clickEvent) {
      window.location = this.toPoeticaUrl;
      return false;
    };

    Poetica.prototype.onTinyMCEClick = function(clickEvent) {
        jQuery('#poetica-tinymce-confirmation').show()
        return false;
    };

    Poetica.prototype.onAllTinyMCEConfirm = function(clickEvent) {
        window.location = this.allTinyMCEUrl;
        return false;
    };

    Poetica.prototype.onTinyMCEConfirm = function(clickEvent) {
        window.location = this.tinyMCEUrl;
        return false;
    };

    Poetica.prototype.onTinyMCECancel = function(clickEvent) {
        jQuery('#poetica-tinymce-confirmation').hide()
        return false;
    };

    Poetica.prototype.onGroupLink = function(clickEvent) {
        var data = {
            verification_token: this.group_auth.verification_token,
            url: this.group_auth.verifyUrl
        };

        var groupLinkReq = jQuery.post(this.poeticaDomain + '/api/wordpress/group', data);

        groupLinkReq.done(this.handleGroupLinkSuccess.bind(this));
        groupLinkReq.fail(this.handleGroupLinkFailure.bind(this));
    };

    Poetica.prototype.onUserLink = function(clickEvent) {
        clickEvent.preventDefault();
        var data = this.user_auth;
        this.verifyUser.apply(this, [data, window.location])
        return false;
    };

    Poetica.prototype.handleGroupLinkSuccess = function (group, status, jqXHR) {
        var groupRelinked = jqXHR.status == 200;
        var data = this.user_auth;
        data['group_access_token'] = group.wordpress_plugin.access_token;

        this.verifyUser.apply(this, [data, window.location, group, groupRelinked]);
    };

    Poetica.prototype.handleGroupLinkFailure = function (group) {
        alert('There was a failure. (Communicating with poetica)');
    }

    Poetica.prototype.verifyUser = function (data, redirectUrl, group, groupRelinked) {
        var verifyUserReq = jQuery.post(this.poeticaDomain + '/api/wordpress/user', data);

        verifyUserReq.done(this.handleVerifyUserSuccess(group, redirectUrl, groupRelinked).bind(this));
        verifyUserReq.fail(this.handleVerifyUserFailure(group).bind(this));
    }

    Poetica.prototype.handleVerifyUserSuccess = function (group, redirectUrl, groupRelinked) {
        return function (user) {
            var newLoc = this.group_auth.saveUrl;

            if (group) {
              newLoc += "&group_access_token=" + group.wordpress_plugin.access_token;
              newLoc += "&group_subdomain=" + group.subdomain;
            }

            newLoc += "&user_access_token=" + user.wordpress.accessToken.token;
            newLoc += "&redirect=" + encodeURIComponent(redirectUrl);

            if(groupRelinked) {
              var relink = confirm('You\'ve used Poetica here before. Do you want us to reconnect your old posts?');
              if(relink) {
                newLoc += "&group_relinked=" + groupRelinked;
              }
            }

            window.location = newLoc;  
        }
    }

    Poetica.prototype.handleVerifyUserFailure = function (group) {
      return function (jqxhr, status, errorThrown) {
        console.log("Poetica Debugging Information", {jqxhr: jqxhr, status: status, errorThrown: errorThrown});
        jQuery('.poetica-modal .poetica-error').show()
        jQuery('.poetica-modal .poetica-content').hide()
        jQuery('#poetica-link-error-confirm').one('click', function (event) {
          event.preventDefault()
          event.stopPropagation()
          jQuery('.poetica-modal .poetica-error').hide()
          jQuery('.poetica-modal .poetica-content').show()
        });
      }
    }

    Poetica.prototype.onFullScreen = function(clickEvent) {
      clickEvent.preventDefault();
      jQuery('body').toggleClass('focus-on');
      jQuery('body').toggleClass('focus-off');
    };

    Poetica.prototype.confirmDeactivate = function(clickEvent) {
      if(!confirm('Deactivating the Poetica plugin will convert all Poetica posts to use the WordPress editor. You won\'t be able to reverse this. Are you sure you want to continue?')) {
        clickEvent.preventDefault();  
      }
    };

    return Poetica;
})();
