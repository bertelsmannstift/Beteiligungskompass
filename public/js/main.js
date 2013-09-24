function setCookie(c_name,value,exdays)
{
    var exdate=new Date();
    exdate.setDate(exdate.getDate() + exdays);
    var c_value=escape(value) + ((exdays==null) ? "" : "; expires="+exdate.toUTCString());
    document.cookie=c_name + "=" + c_value;
}
function getCookie(c_name)
{
    var i,x,y,ARRcookies=document.cookie.split(";");
    for (i=0;i<ARRcookies.length;i++)
    {
      x=ARRcookies[i].substr(0,ARRcookies[i].indexOf("="));
      y=ARRcookies[i].substr(ARRcookies[i].indexOf("=")+1);
      x=x.replace(/^\s+|\s+$/g,"");
      if (x==c_name)
        {
        return unescape(y);
        }
      }
}

function getRandom(min, max) {
 if(min > max) {
  return -1;
 }

 if(min == max) {
  return min;
 }

 var r;

 do {
  r = Math.random();
 }
 while(r == 1.0);

 return min + parseInt(r * (max-min+1));
}

var main = {

    youtubeFeedUrl: 'http://gdata.youtube.com/feeds/api/videos/',
    isMobile: false,
    isTablet: false,
    activeArticleList: [],
    activeFavArticleList: [],
    selectedText: '# of # selected',
    selectHeaderText: 'Select option',
    selectNoneSelectedText: 'Select option',
    lastEventTarget: null,

    showOverlay: function(overlayId, url, opt) {
        $('#exposeMask').remove();
        /*
         * deactivating the cache is important for IE9 and maybe other browsers
         * otherwise they might use a cached profile from another user that was logged
         * in before
         */
        $.ajax({
          url: url,
          cache: false,
          dataType: 'html',
          type: 'get',
          success: function(data) {
              $('div.overlay, #exposeMask').remove();

              $('#wrapper').append(data);
              main.initOverlay(overlayId);
              $(".main-box").unmask();

              if($(overlayId).find('form.ajax-form').length) {
                  $(overlayId).find('form.ajax-form').ajaxForm({method: 'post'});
              }

              if(main.isMobile) {
                  $('#ask-overlay form, #reg-overlay form, #login-overlay form, #fav-overlay form, #profile-overlay form, #lostpassword-overlay form, #removeacc-overlay form').submit(onSubmit);
              }

	          if(typeof opt != 'undefined' && opt) {
                  opt();
              }
          }
        });
    },

    initOverlay: function(overlayId, $customOpts) {
        if(!main.isMobile) {
            $('#exposeMask').remove();
            var options = {mask: {color: '#000', opacity: 0.4}, load: true, closeOnClick: false, close: '.close'};
            if(typeof $customOpts != 'undefined') {
                options = $.extend(options, $customOpts);
            }
            $(overlayId).overlay(options);
        }
        else {
            var bodyElements = $('#wrapper > *:not(#' + overlayId + ')');
            bodyElements.hide();
            $(overlayId).addClass('mobile');

            var closeFunction = function(){
                $('div.overlay').remove();
               bodyElements.show();
               if(typeof $customOpts != 'undefined' && typeof $customOpts.onClose != 'undefined') {
                   $customOpts.onClose();
               }
            };

            $(overlayId).find('.close').click(closeFunction);

            window.scrollTo(0,0);
        }
        $('.closeoverlay').click(function(){$('.close').click();});
        try {
            $(overlayId).find('input:first').focus();
        } catch(e) {}

        main.fixButtons();
    },

    getArticleTypeCountFunc: function(noUpdate) {
        var sumArticles = 0;

        if(typeof articleTypeCount != 'undefined' && articleTypeCount != null) {
            $.each(articleTypeCount.filtered, function(key, val){
                if(typeof noUpdate == 'undefined') {

                    if(val == articleTypeCount.all[key]) {
                        var html = '<span>' + val + '</span>';
                    } else {
                        var html = '<span class="yellow">' + val + ' / ' + articleTypeCount.all[key] + '</span>';
                    }

                    $('#result-'+key+' > span:last, .result-'+key+' > span:last').html(html);
                }

                sumArticles += parseInt(val);
            });
        }

        return sumArticles;
    },

    init: function() {

        main.isMobile = $('body').hasClass('mobile');
        main.isTablet = $('body').hasClass('tablet');
        main.selectedText = selectedText;
        main.selectHeaderText = selectHeaderText;
        main.selectNoneSelectedText = selectNoneSelectedText;

        if(!main.isMobile && $("a[rel^='prettyPhoto']").length) {
            $("a[rel^='prettyPhoto']").prettyPhoto({social_tools: '',markup:
				'<div class="pp_pic_holder"> \
					<div class="ppt">&nbsp;</div> \
					<div class="pp_top"> \
						<div class="pp_left"></div> \
						<div class="pp_middle"></div> \
						<div class="pp_right"></div> \
					</div> \
					<div class="pp_content_container"> \
						<div class="pp_left"> \
							<div class="pp_right"> \
								<div class="pp_content"> \
									<div class="pp_loaderIcon"></div> \
									<div class="pp_fade"> \
										<a class="pp_close" href="#">Close</a> \
										<div class="pp_hoverContainer"> \
											<a class="pp_next" href="#">next</a> \
											<a class="pp_previous" href="#">previous</a> \
										</div> \
										<div id="pp_full_res"></div> \
										<div class="pp_details"> \
											<div class="pp_nav"> \
												<a href="#" class="pp_arrow_previous">Previous</a> \
												<p class="currentTextHolder">0/0</p> \
												<a href="#" class="pp_arrow_next">Next</a> \
											</div> \
											<p class="pp_description"></p> \
											<div class="pp_social">{pp_social}</div> \
											<a href="#" class="pp_expand" title="Expand the image">Expand</a> \
										</div> \
									</div> \
								</div> \
							</div> \
						</div> \
					</div> \
					<div class="pp_bottom"> \
						<div class="pp_left"></div> \
						<div class="pp_middle"></div> \
						<div class="pp_right"></div> \
					</div> \
				</div> \
				<div class="pp_overlay"></div>'
			});
        }

        $.each($('[data-rel]'), function() {
            // for html5 validation
            $(this).attr('rel', $(this).data('rel'));
        });

        if($.cookie('filter') != null && $.cookie('filter') != '') {
            main.activeArticleList = $.cookie('filter').split('@/@');
        }

        if($.cookie('filter_fav') != null && $.cookie('filter_fav') != '') {
            main.activeFavArticleList = $.cookie('filter_fav').split('@/@');
        }

        main.updateFloatingDivHeight();

        if(typeof articleTypeCount != 'undefined' && articleTypeCount != null) {
            $.each(articleTypeCount, function(key, val){
                $('#result-'+key+' > span:last, .result-'+key+' > span:last').html(val);
            });
        }

        $('body').on('click', '.entrylist .headline-type:not(.single-view)', function(){
            var $this = $(this);
                $this.nextUntil('.headline-type').toggleClass('show');
                $this.toggleClass('show');
            var typeClassName = $this.data('open');

            if($this.hasClass('show')) {
                for(var i in main.activeArticleList) {
                    if(main.activeArticleList[i] == typeClassName) {
                        main.activeArticleList.splice(i,1);
                        $.cookie('filter', main.activeArticleList.join("@/@"));
                    }
                }
            } else {
                var inarray = false;
                for(var i in main.activeArticleList) {
                    if(main.activeArticleList[i] == typeClassName) {
                        inarray = true;
                    }
                }
                if(inarray == false) {
                    main.activeArticleList.push(typeClassName);
                    $.cookie('filter', main.activeArticleList.join("@/@"));
                }
            }
            main.updateFloatingDivHeight();
        });

        $('body').on('click', '.entrylist .headline-type-fav', function(){
			var $this = $(this);
            $this.toggleClass('show');

            var typeClassName = $this.data('open');
            if($this.hasClass('show')) {
                for(var i in main.activeFavArticleList) {
                    if(main.activeFavArticleList[i] == typeClassName) {
                        main.activeFavArticleList.splice(i,1);
                        $.cookie('filter_fav', main.activeFavArticleList.join("@/@"));
                    }
                }
            } else {
                var inarray = false;
                for(var i in main.activeFavArticleList) {
                    if(main.activeFavArticleList[i] == typeClassName) {
                        inarray = true;
                    }
                }
                if(inarray == false) {
                    main.activeFavArticleList.push(typeClassName);
                    $.cookie('filter_fav', main.activeFavArticleList.join("@/@"));
                }
            }

            if(!$this.hasClass('show')) {
                $this.siblings().hide();
            } else {
                var groupId = $('.fav-group.show').children('span:first').data('href').replace('#','');

                if(groupId == 'not-assigned') {
                     $this.siblings('.fav-box').hide();
                     $this.siblings('.fav-box[data-groups="[]"]').show();
                    main.updateFloatingDivHeight();
                    return;
                }
                else if(groupId == '') {
                     $this.siblings('.fav-box').show();
                    main.updateFloatingDivHeight();
                    return;
                }
                $this.siblings('.fav-box').hide();
                $.each($this.siblings('.fav-box'), function(){
                    var box = $(this)
                    var obj = $(this).data('groups');

                    $.each(obj, function(k, v){
                        if(v == groupId) {
                            box.show();
                        }
                    });
                });
            }

			main.updateFloatingDivHeight();
		});


        $('#article_filter').bind('complete',function(e, content, old_input){

            articleTypeCount = content;

            var resetLastChange = function() {
                if(old_input && old_input.length) {

                    if(old_input.is('select')) {
                        old_input.find("option:first").prop("selected","selected");
                    }
                    else if(old_input.is('[type=radio]')) {
                        old_input.parents('.con:first').find('.default-opt').attr('checked', 'checked');
                        if(old_input.parents('.box-resource').length) {
                            var radio = old_input.parents('.box-resource').find(':radio').first();
                            radio.parents('.box-resource').find('.slide').slider('value', radio.parent().index());
                        }
                    }
                    else {
                        if(old_input.is(':checked')) {
                            old_input.removeAttr('checked');
                        } else {
                            old_input.attr('checked', 'checked');
                        }
                    }

                    $('#article_filter').trigger('update');
                }
            };

            if(main.getArticleTypeCountFunc(true) == 0) {
                alert(noResult);

                if($('[name=search]').val().length) {
                    $('#reset-filter:first').click();
                    return true;
                }
                resetLastChange();
            }
            else if(articleTypeCount.filtered.method == 0) {
                alert(noResult);
                resetLastChange();
            }

            $.each(articleTypeCount.filtered, function(key, val){
                if(val == articleTypeCount.all[key]) {
                     var html = '<span>' + val + '</span>';
                } else {
                     var html = '<span class="yellow">' + val + ' / ' + articleTypeCount.all[key] + '</span>';
                }
                $('#result-'+key+' > span:last').html(html);
            });

            if($('#result_view_filter').length) {
                $('#article_filter').mask(msgLoading);
                $('#result_view_filter').trigger('update', [true, $('#article_filter').serialize()]);
            }

            main.planningSelected();
            main.updateFloatingDivHeight();
	        main.updatePlanningFilters(false);

        });

        if($('body').hasClass('article_index')) {
            // jump to anker on article index page
            if(window.location.hash && parseInt(window.location.hash.replace('#','')) > 0) {
                $(window).scrollTop(window.location.hash.replace('#',''));
            }

            $.each(main.activeArticleList, function(k,e){
                $('[data-open=' + e + ']:.show').click();
            });

            $('#result_view_filter').bind('complete',function(e, content, old_input){
                $('#article_filter').unmask();

                $.each(main.activeArticleList, function(k,e){
                    $('[data-open=' + e + ']:not(.show)').click();
                });

                filtered();
                main.getArticleTypeCountFunc();
                $('.result-count').html($('#result-'+currentType+' span').html());

                $("img.lazy").lazyload({
                    effect : "fadeIn"
                });
	            // for lazyload without scroll
	            $(window).scroll();

                main.initMultiSelects();
                if(!main.isMobile) {
                    main.initSlider();
                }
                main.checkForSelectRemove();
                main.updatePlanningFilters();
                main.updateFloatingDivHeight();
	            main.initSearchForm();


	            if($('.more-filters > label').length == 0) {
                    $('#more-filters, .more-filters').hide();
                }

                $("a[rel].overlay").click(function(event){
                    event.preventDefault();

                    if(!$(this).hasClass('overlay') || $(this).hasClass('disabled') || $(this).hasClass('active')) {
                        return;
                    }
                    else if($(this).hasClass('need-user') && userId == '') {
                        return;
                    }

                    main.lastEventTarget = $(event.target).parent('.button').length ? $(event.target).parent().get(0) : event.target;
                    $(".main-box").mask(msgLoading);
                    main.showOverlay($(this).attr('rel'), $(this).data('href'));
                });

            });
        } else if($('body').hasClass('planning_index')) {

            if(main.isMobile == false && $('.side-scrollbox').length) {
                var $scrollbox = $('.side-scrollbox');

                $scrollbox.css({position:'relative', 'top': '0'});
                var orgPos = $scrollbox.offset().top - $(window).scrollTop();
                var boxPadding = ($(window).height() / 2) - ($scrollbox.height() / 2) - 50;

                var func = function(e) {
                    if($(window).scrollTop()+boxPadding > orgPos) {
                        $scrollbox.css({position:'fixed', 'top': boxPadding, 'z-index': '2'});
                    }
                    else if($(window).scrollTop() <= orgPos) {
                        // reset position
                        $scrollbox.css({position:'relative', 'top': '0'});
                    }
                    main.updateFloatingDivHeight();
                };

                $(window).scroll(func);
                $(window).bind('touchmove', func);
            }
        }
        else if($('body').hasClass('news_edit')) {
            $('form').bind('complete',function(e, content){
                $('.dateinput input[type=text], input.date').datepicker(datepickerConfig);
            });
        }
        else if($('body').hasClass('article_edit')) {
            $('form').bind('complete',function(e, content){
                $('.dateinput input[type=text], input.date').datepicker(datepickerConfig);
                $('.time').timepicker(timerpickerConfig);
            });
        }
        else if($('body').hasClass('favorites_index') && window.location.hash.length > 0) {
            var article_link_id = window.location.hash;

            if(window.location.hash.indexOf('#allentries') != -1) {
                $('#allentries').attr('checked', 'checked');
                $('form').trigger('update', [false]);
                window.location.hash = window.location.hash.replace('#allentries-', '#article-');
            }

            if(window.location.hash.indexOf('#submitted') != -1) {
                article_link_id = window.location.hash.replace('#submitted-', '#article-');
            }
        }

        $('#fav-form').bind('complete',function(e, content){

	        if($.cookie('active_fav_group')) {
		        $('.fav-group[data-id="' + $.cookie('active_fav_group') + '"]').click();
	        } else {
		        $('.fav-group:first').click();
	        }

	        main.initMultiSelects();
            main.calcNotAssignedToGroupArticles();
            $(".newgroup").click(function(event){
                                event.preventDefault();
                                main.lastEventTarget = event.target;
                                $(".main-box").mask(msgLoading);
                                main.showOverlay($(this).data('rel'), $(this).data('href'));
                            });
            $('.fav-group.show').click();

            $.each(main.activeFavArticleList, function(k,e){
                $('[data-open=' + e + ']:.show').click();
            });
        });

        $('form').bind('complete',function(e, json){
            if(typeof CKEDITOR != 'undefined') {
                CKEDITOR.instances = [];

                $('textarea.wysiwyg').ckeditor();
            }
        });


        $('body').on('complete', '#fav-overlay form', function(event, json) {
            if(json.success == true) {
                $('.close').click();

                $(main.lastEventTarget).addClass('active');
                $(main.lastEventTarget).removeClass('overlay');
                $(main.lastEventTarget).unbind('click');

                var counterElement = $('.content-head a.fav span'),
             				count = parseInt(counterElement.text(),10),
             				newCount = 0;
                counterElement.text(count + 1);
                newCount = parseInt($('#fav-count').text(), 10) +1;
                $('#fav-count').text(newCount);
            }
        });

        $('[name=search]').keyup(function() {
            var input = $(this);
            $.each($('[name=search]'), function(){
                if($(this).val() != input.val()) {
                    $(this).val(input.val());
                }
            });
        });

        $('.dateinput input[type=text], input.date').datepicker(datepickerConfig);

        if(main.isMobile) {
            $('body').on('click', 'a.filter-head', function(e){
                e.preventDefault();
                $('.side-menu.filter').toggle();
                $(this).toggleClass('show');
            });

            // hide fileupload on none android
            if(!navigator.userAgent.match(/android/i)) {
                $('#file_upload_container').hide();
            }

            var minheight = 0;
            var setHeight = function() {
                minheight = window.innerHeight;

                $('#content').css('min-height', minheight - 120);
            };
            $(window).bind('orientationchange', function(){
                setHeight();
            });
            setHeight();
	        $('.side-menu.filter').hide();
        }

        $('body').on('click', '.article-link', function(e){
            var id = $(window).scrollTop();
            window.location.hash = id;
        });

	    $('body').on('click', 'a.ownentry', function(e){
            e.preventDefault();
            if(!userId) {
                return;
            }

            var overlayId = $(this).attr('rel');
            $(overlayId).remove();
            $.ajax({
                url: $(this).attr('href'),
                dataType: 'html',
                type: 'get',
                success: function(data) {
                    $('#wrapper').append(data);
                    if(!main.isMobile) {
                        $(overlayId).overlay({mask: {color: '#000', opacity: 0.4}, load: true, close: '.close'});
                    }
                    else {
                        $('#wrapper > *:not(#' + overlayId + ')').hide();
                        $(overlayId).addClass('mobile');
                        var closeFunction = function(){
                                                $('div.overlay').remove();
                                                $('#wrapper > *:not(#' + overlayId + ')').show();
                                            };

                        $(overlayId).find('.close').click(closeFunction);
                        window.scrollTo(0,0);
                    }
                }
            });
        });

        $('.datetimeinput #form_start_date').bind('update', function() {
            $('.datetimeinput #form_end_date').val($(this).val());
            $('.datetimeinput #form_end_date').siblings('.date').val($(this).siblings('.date').val());
            $('.datetimeinput #form_end_date').siblings('.time').val($(this).siblings('.time').val());
        });

        $('body').on('click', "a.reg", function(event) {
            event.preventDefault();
            main.showOverlay('#reg-overlay', registerUrl);
        });

        $('body').on('click', "a.link", function(event) {
            event.preventDefault();
            main.showOverlay('#lostpassword-overlay', lostpasswordUrl);
        });

        $('body').on('click', '.need-user', function(e){
            if(!userId) {
                e.preventDefault();
                main.showOverlay('#login-overlay', loginUrl);
            }
        });

        $('body').on('click', '.ask', function(e){
            e.preventDefault();
            if(!userId) {
                main.showOverlay('#login-overlay', loginUrl);
            } else {
                main.showOverlay('#ask-overlay', askUrl + '?id=' + article_id);
            }
        });

        $('body').on('click', '.share', function(e){
            main.showOverlay('#share-overlay', shareUrl + (typeof article_id == 'undefined' ? '?type=' + articleType + '&url=' + shareConfUrl : '?id=' + article_id));
        });

        $('body').on('click', '.share-group', function(e){
            e.preventDefault();
            $link = $(this);
            var group = $('.group.show').data('groupid');
            $.ajax({
              url: shareGroupUrl,
              dataType: 'json',
              type: 'post',
              data: {group: group},
              success: function(json) {
                 main.showOverlay('#share-overlay', shareUrl + '?url=' + json.link + '&type=' + shareGroupTitle + '&url_only=1');
              }
            });
        });

        $("body").on("click", "a[rel].overlay", function(event){
            if(!$(this).hasClass('overlay') || $(this).hasClass('disabled') || $(this).hasClass('active')) {
                return;
            }
            else if($(this).hasClass('need-user') && userId == '') {
                return;
            }

            event.preventDefault();
            main.lastEventTarget = $(event.target).parent('.button').length ? $(event.target).parent().get(0) : event.target;
            $(".main-box").mask(msgLoading);
            main.showOverlay($(this).attr('rel'), $(this).data('href'));
        });

        // init youtube input fields
        $('.input.youtube input').blur(function() {
            var $this = $(this);
            var url = $this.val();
            $this.parents('.row').find('.youtube-box').remove();

            var regExp = /^.*((youtu.be\/)|(v\/)|(\/u\/\w\/)|(embed\/)|(watch\?))\??v?=?([^#\&\?]*).*/;
            var match = url.match(regExp);
            var vidId = null;
            if (match && match[7].length==11){
                vidId = match[7];
            } else {
                $(this).val('');
                return;
            }

            $.getJSON( main.youtubeFeedUrl + vidId + "?v=2&alt=jsonc&callback=?", function(json) {
                var $youtubeForm = $(youtube_tpl);
                $youtubeForm.find('img').attr('src', json.data.thumbnail.sqDefault);

                $youtubeForm.find('a').attr('href', json.data.player['default']);
                $youtubeForm.find('a').attr('target', '_blank');

                $youtubeForm.find('a').html('http://www.youtube.com/watch?v=' + vidId);
                $youtubeForm.find('strong').html(json.data.title);
                $youtubeForm.find('.text div').html(json.data.description.substr(0, 50) + (json.data.description.length > 50 ? '...' : ''));
                $this.closest('.row').append($youtubeForm);
            });
            main.updateFloatingDivHeight();
        });


        $('.load-thumb-vimeo').each(function(){
            var $this = $(this);
            $.ajax("http://vimeo.com/api/v2/video/" + $this.data('id') + ".json?callback=?", {
                dataType: 'jsonp',
                type: 'GET',
                crossDomain: true,
                success: function(json) {
                    var img = $('<img/>');
                    img.attr('src', json[0].thumbnail_small);
                    img.attr('alt', '');
                    img.attr('height', '45');
                    $this.append(img);
                    main.updateFloatingDivHeight();
                }
            });
        });

        $('.load-thumb-youtube').each(function(){
            var $this = $(this);
            $.ajax({
              url: main.youtubeFeedUrl + $this.data('id') + "?v=2&alt=jsonc&callback=?",
              dataType: 'json',
              crossDomain: true,
              success: function(json) {
                  var img = $('<img/>');
                  img.attr('src', json.data.thumbnail.sqDefault);
                  img.attr('alt', '');
                  img.attr('height', '45');
                  img.removeAttr('width');
                  $this.append(img);
                  main.updateFloatingDivHeight();
              }
            });
        });

        // init youtube inputs on page refresh
        $('.input.youtube input:empty').blur();

        // add remove button to filled inputs
        $('.input.youtube input').addRemoveInputIcon();

        // accordion
        $('.applied-head .toggle').click(function(){
            if($(this).hasClass('plus')) {
                $(this).parents('.applied').find('.applied-content').show();
                $(this).removeClass('plus');
            } else {
                $(this).parents('.applied').find('.applied-content').hide();
                $(this).addClass('plus');
            }
        });

        // accordion
        $('.toggleall').click(function(){
            if($(this).hasClass('plus')) {
                $(this).parents('.box-content').find('.head:not(.hideCat)').siblings('.selected_values').toggle();
                $(this).parents('.box-content').find('.head:not(.hideCat)').click();

                $(this).removeClass('plus');
            } else {
                $(this).parents('.box-content').find('.head.hideCat').siblings('.selected_values').toggle();
                $(this).parents('.box-content').find('.head.hideCat').click();

                $(this).addClass('plus');
            }
        });

        // resolve browser back button filter problem
        //$('#result_view_filter').trigger('update');


        $('body').on('click', '.fav-group', function(e){
            $.cookie('active_fav_group', $(this).data('id'));

            $('.fav-group').removeClass('show');
            $(this).addClass('show');

            $(this).siblings('.share-link').remove();

            if($(this).hasClass('group')) {
                $('.form-box.share-box').show();
            } else {
                $('.form-box.share-box').hide();
            }

            if (e.originalEvent == 'undefined')
            {
                $('#fav-category option:first').attr('selected', 'selected');
                $(".side-menu select:not([multiple])").multiselect('refresh');
            }

            var typeGroups = function() {
                $('.type-container').show();
                $.each($('.type-container'), function() {
                    if($(this).find('.entry:visible').length == 0) {
                        $(this).hide();
                        $(this).find('.headline-type-fav').removeClass('show');
                    } else {
                        $(this).find('.headline-type-fav').addClass('show');
                    }
                });
                main.calcNotAssignedToGroupArticles();
                main.updateFloatingDivHeight();
            };

            var groupId = $(this).children('span:first').data('href').replace('#','');
            if(groupId == 'not-assigned') {
                $('.fav-box').hide();
                $.each($('.fav-box'),function(){
                    if($(this).data('groups').length == 0) {
                        $(this).show();
                    }
                });
                typeGroups();
				return;
            }
			else if(groupId == '') {
                $('.fav-box').show();
                typeGroups();
                return;
            }
            $('.fav-box').hide();
            $.each($('.fav-box'), function(){
                var box = $(this)
                var obj = $(this).data('groups');

                $.each(obj, function(k, v){
                    if(v == groupId) {
                        box.show();
                    }
                });
            });
            typeGroups();
	        main.updateFloatingDivHeight();
        });

		if($.cookie('active_fav_group')) {
			$('.fav-group[data-id="' + $.cookie('active_fav_group') + '"]').click();
		} else {
			$('.fav-group:first').click();
		}
        // simulate click on favorites group, used within favorites/showgroup
        $('.fav-group.shown').removeClass('shown').click();
	    setTimeout(function(){main.updateFloatingDivHeight();}, 1000);

        main.calcNotAssignedToGroupArticles();

        $('body').on('change', '#fav-category', function(){
            $('.fav-group.show').click();
            $('.type-container, .nogroup').show();
            var classNameStr = ($(this).val() == '' ? '' : '.' + $(this).val()).toString();

            if(classNameStr.length > 0 && $('.type-container' + classNameStr + ' .fav-box:visible, ' + classNameStr + '.nogroup:visible').length > 0) {
                $('.type-container, .nogroup').hide();
	            $('.type-container' + classNameStr).show();
                $('.nogroup' + classNameStr).show();
	            main.updateFloatingDivHeight();
                return;
            } else if(classNameStr.length == 0)  {
                $('.fav-group.show').click();
	            main.updateFloatingDivHeight();
                return;
            }
            $('.type-container, .nogroup').hide();
            main.updateFloatingDivHeight();
        });

        if(main.isTablet == false) {
            // content-head menu items hover
            $('.content-head ul a').hover(function(){
                var classname = $(this).parent().attr('class');
                $('.content-head .text > div').hide();
                $('.content-head .text .' + classname).show();
            }, function(){
                var classname = $('.content-head ul a.active').parent().attr('class');
                $('.content-head .text > div').hide();
                $('.content-head .text .' + classname).show();
            });
        }

	    $('body').on('click', '#reset-filter, .reset-filter', function(e){
            e.preventDefault();

            $('.reset-filter').hide();
            $('.right-criterias').css('padding-top', '0px');

            $('#article_filter .category option:selected, #article_filter .category [type="radio"]:checked, #article_filter .category [type=checkbox]:checked, [name=search][value!=], .box.criteria-visible select option:not(.all-opt):selected, .box.criteria-visible input[type=radio]:checked:not(.default-opt), .box.criteria-visible .resource input[type=radio]:checked:not(.default-opt), .box.criteria-visible .checkbox input[type=checkbox]:checked:not(.default-opt)').removeAttr('checked').removeAttr('selected');

            $("select").each(function(){
                $(this).find("option.all-opt").prop("selected","selected");
            });
            $('#article_filter .category:visible [type="radio"].default-opt, .radio input[type=radio].default-opt, .resource input[type=radio].default-opt').attr('checked', 'checked');
            //IE FIX
            $('#article_filter .category:visible [type="radio"].default-opt, .radio input[type=radio].default-opt, .resource input[type=radio].default-opt').get(0).checked = true;


            $(".side-menu.filter select").multiselect('refresh');
            main.checkForSelectRemove();


            var radio = $('.box-resource').find('.default-opt');

            if(radio.parents('.box-resource').find('.slide').length) {
                radio.parents('.box-resource').find('.slide').slider('value', radio.parent().index());
            }

            $('form').trigger('update');
            filtered();
        });

        var filtered = function(){
            // Disabled
            return;

            if($('[name=search][value!=], .box.criteria-visible select option:not(.all-opt):selected, .box.criteria-visible .radio input[type=radio]:checked:not(.default-opt), .box.criteria-visible .resource input[type=radio]:checked:not(.default-opt), .box.criteria-visible .checkbox input[type=checkbox]:checked:not(.default-opt)').length > 0) {
                $('#filtered').addClass('show');
            } else {
                $('#filtered').removeClass('show');
            }
        };

        $('.radio input[type=radio]:checked, .checkbox input[type=checkbox]:checked').change(filtered);
        $('.radio input[type=radio]:checked, .checkbox input[type=checkbox]:checked').click(filtered);

        filtered();

        if(typeof CKEDITOR != 'undefined') {
            $('textarea.wysiwyg').ckeditor();
	        if($('body').hasClass('backend')) {
		        $('textarea.wysiwyg-link').ckeditor({toolbar: [
		               [ 'Link','Unlink','Anchor'  ],
		               '/',
		               ['Image'],
		               '/',
		               ['FontSize', 'Bold','Italic','Underline','StrikeThrough','-','Undo','Redo','-','Cut','Copy','Paste','Find','Replace','-','Outdent','Indent','-','Print'],
		               '/',
		               ['NumberedList','BulletedList','-','JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock']],
		                filebrowserUploadUrl  :'/backend/pages/imgupload'});
	        } else {
		        $('textarea.wysiwyg-link').ckeditor({toolbar: [[ 'Link','Unlink','Anchor'  ]]});
	        }
        }

        if(typeof article_type != 'undefined') {
            $('li.' + article_type + ':not(.active)').addClass('active');
        }

	    $('body').on('click', '.remove-filter-opt', function(e){
		    e.preventDefault();
		    $('[value="' + $(this).data('id') + '"]').removeAttr('selected').removeAttr('checked').change().parent().change();
	    });

	    main.initMultiSelects();
        main.fixButtons();
        main.initToggleForPlanning();
        main.planningSetDefaultOptionsSelected();
        main.planningSelected();
		main.initVideoPager();
		main.updateFloatingDivHeight();
	    main.updatePlanningFilters();
	    main.initSlider();
	    main.initSearchForm();
	    main.initMobileNav();


	    if($('.more-filters > label').length == 0) {
		    $('#more-filters, .more-filters').hide();
	    }

        $.each(main.activeFavArticleList, function(k,e){
            $('[data-open=' + e + ']:.show').click();
        });


        $('.planning-headline').click(function() {
            $('.toggle-planning').removeClass('active');
            $('.planning-box-config').css('height', '0');
        });

        $('body').on('click', '.groups a.remove', function(e){
            e.preventDefault();
            var article = $(this).data('article');
            var group = $(this).data('group');
            var link = $(this);
            $.ajax({
              url: removeFromGroupUrl,
              dataType: 'json',
              type: 'post',
              data: {group: group, article: article},
              success: function(json) {

                  // sidemenu item
                  var count = parseInt($('#group-' + group).children('span').html());
                  $('#group-' + group).children('span').html(--count);

                  var favbox = link.parents('.fav-box');
                  favbox.find('select option[value=' + group + ']').removeAttr('disabled');

                  var obj = favbox.data('groups');
                  obj = obj.removeItem(group);
                  favbox.data('groups', obj);

                  if(link.parent().parent().find('span:not(.group-title)').length == 1) {
                      link.parents('.groups').hide();
                  }
                  link.parent().remove();
                  $('.fav-group.show').click();
                  main.calcNotAssignedToGroupArticles();
              }
            });
        });

        if(typeof CKEDITOR != 'undefined') {
            $('.time').timepicker(timerpickerConfig);
        }

        if($.browser.msie) {
            // dont display errors in IE - because ckeditor throws one
            window.onerror = function(){return true;};
        }

        if(typeof domready == 'function') {
            domready();
        }

        $("img.lazy").lazyload({
            effect : "fadeIn"
        });
		// for lazyload without scroll
	    $(window).scroll();

        $('.criteria-toggle .toggle').click(function(){
            $('.criteria-toggle-content').toggleClass('show');
            $(this).toggleClass('show');
            main.updateFloatingDivHeight(true);
        });

        main.checkForSelectRemove();
        main.updateFloatingDivHeight();

        if(window.location.hash == '#login') {
            main.showOverlay('#login-overlay', loginUrl);
            window.location.hash = '';
        }
        else if(window.location.hash == '#register') {
            main.showOverlay('#reg-overlay', registerUrl);
            window.location.hash = '';
        }
        else if(window.location.hash == '#lostpassword') {
            main.showOverlay('#lostpassword-overlay', lostpasswordUrl);
            window.location.hash = '';
        }
        else if(window.location.hash.indexOf('#submitted') != -1) {
            main.showOverlay('#submitted-overlay', submittedUrl);
            window.location.hash = '';
        }
        else if(window.location.hash.indexOf('#planning') != -1) {
            $('.toggle-planning').click();
            $(window).scrollTop($('.content-head').offset().top)
        }
    },

	initSearchForm: function() {
		var term = $.trim($('[name="search"]').val());

		if(term == '') {
			$('.reset-search').hide();
		} else {
			$('#search-head form').find('input').val(term);
			$('.reset-search').css('display', 'block');
		}

		$('.reset-search').unbind('click').click(function(){
			$('[name="search"], [name="term"], #search-head form input').val('');
			$('#result_view_filter').trigger('update');
		});
	},

    updatePlanningFilters: function(remove_planning) {
	    remove_planning = remove_planning != 'undefined' ? remove_planning : false;

	    if(typeof articleTypeCount != 'undefined' && articleTypeCount != null) {
		    var casesAndStudies = parseInt(articleTypeCount.filtered['study']) + parseInt(articleTypeCount.filtered['method']);
		    $('.result-study span:last-child').html(articleTypeCount.filtered['study']);
		    $('.result-method span:last-child').html(articleTypeCount.filtered['method']);
		    $('.result-toggle-count').html(casesAndStudies);
	    }


	    if(remove_planning == true) {
	      $('#article_filter option').removeAttr('selected');
	      $('#article_filter [type=radio]').removeAttr('checked');
	      $('#article_filter [type=checkbox]').removeAttr('checked');
	    }

	    var filterActive = false;
	    $('.reset-filter').hide();
	    $('.right-criterias').css('padding-top', '0px');

        $.each($('#result_view_filter option:selected'), function() {
            var $planningOpt = $(this);
            var val = $planningOpt.val();
            $('option[value="' + val + '"]').attr('selected', 'selected');
            $('input[value="' + val + '"]').attr('checked', 'checked');
        });
        $.each($('#result_view_filter [type="radio"]:checked'), function() {
            var $planningOpt = $(this);
            var val = $planningOpt.val();
            $('#article_filter [type="radio"][value="' + val + '"]').attr('checked', 'checked');
            main.initSlider($('#article_filter [type="radio"][value="' + val + '"]').parents('.con'));
        });
        $.each($('#result_view_filter [type=checkbox]:checked'), function() {
            var $planningOpt = $(this);
            var val = $planningOpt.val();
            $('option[value="' + val + '"]').attr('checked', 'checked');
        });
        $("#article_filter select").multiselect('refresh');

		$.each($('#result_view_filter .box:visible option:selected, #result_view_filter .box:visible [type="radio"]:checked, #result_view_filter .box:visible [type=checkbox]:checked, #article_filter .category option:selected, #article_filter .category [type=checkbox]:checked, #article_filter .category [type="radio"]:checked'), function() {
		    var $planningOpt = $(this);
			if(!$planningOpt.hasClass('default-opt') && !$planningOpt.hasClass('all-opt')) {
				filterActive = true;
			}
		});

	    if($('.side-menu .planning label').length) {
		    filterActive = true;
	    }

	    if(filterActive == true && $.trim($('[name="search"]').val()) == '') {
		    $('.reset-filter').css('display', 'block');
		    if($('#article_filter > h2').height() <= 50) {
			    $('.right-criterias').css('padding-top', '20px');
		    }
	    }
    },

	initMobileNav: function() {
		if(main.isMobile) {
			// prevent default logo link
			$('.head-logo a').click(function(e){
				e.preventDefault();
			});
			// logo click menu
			$('.head-logo').click(function(){
				if($(this).hasClass('active')) {
					$(this).removeClass('active');
					$('#mobile-nav, #mobile-menu-bg').hide();
				} else {
					$(this).addClass('active');
					$('#mobile-nav, #mobile-menu-bg').show();
				}
			});
			// sub nav show/hide
			/*
			$('#mobile-nav li ul').siblings('a').click(function(e){
				e.preventDefault();
				$(this).siblings('ul').toggle();
			});
			*/

			// mobile Search
			$('#mobile-search input[type=text]').click(function(){
				if($(this).val().length > 0) {
					$(this).select();
				}
			});
			if('undefined' != typeof searchTerm) {
				$('#mobile-search input[type=text]').val(searchTerm);
			}
		}
	},

    initMultiSelects: function() {

        var multiselect_options_for_side_menu = {
           multiple: false,
           header: main.selectHeaderText,
           noneSelectedText: main.selectNoneSelectedText,
           selectedList: 1,
           click: main.checkForSelectRemove,
           position: {
              my: 'right top',
              at: 'right bottom'
           },
           classes: 'side-menu-select' + ($(this).parents('.box-content-with-menu').length ? ' select-in-content' : ' select-in-head')
        };


        $("select:not([multiple])").filter(function(index) {
          return $(this).parents('#article_form_steps').length == 0;
        }).multiselect(multiselect_options_for_side_menu);


        var multiselect_options_for_article_filter = {
            multiple: false,
            noneSelectedText: main.selectNoneSelectedText,
            selectedList: 1,
            click: main.checkForSelectRemove,
            minWidth: 150,
	        height: 'auto',
            position: {
                my: 'right top',
                at: 'right bottom'
            },
            classes: 'default-select' + ($(this).parents('.box-content-with-menu').length ? ' select-in-content' : ' select-in-head')
        };

        $("#article_filter select:not([multiple]), .inputfields select:not([multiple])").filter(function(index) {
          return $(this).parents('#article_form_steps').length == 0;
        }).each(function(){

            multiselect_options_for_article_filter.header = main.selectHeaderText;

            // the sort select menu should have no header / close option
            if ( $(this).attr('id') === 'sortby' || $(this).attr('name') === 'orderby' ) {
                multiselect_options_for_article_filter.header = false;
            }

            $(this).multiselect(multiselect_options_for_article_filter);
        });


        var multiselect_options_for_multiple_selects = {
            selectedText: main.selectedText,
            noneSelectedText: main.selectNoneSelectedText,
            header: main.selectHeaderText,
            position: {
                my: 'right top',
                at: 'right bottom'
            },
            classes: 'side-menu-select' + ($(this).parents('.box-content-with-menu').length ? ' select-in-content' : ' select-in-head'),
            click: main.checkForSelectRemove
        };

        $("select[multiple]").multiselect(multiselect_options_for_multiple_selects);

    },

    initVideoPager: function() {
        var $featurArticleWrapper = $('body.welcome_index');

        if(!$featurArticleWrapper.length) {
            return false;
        }

        $featureArticles = $featurArticleWrapper.find('.feature-article'),
        $pagerBox = $featurArticleWrapper.find('.pager'),
        $pagerButtons = $featurArticleWrapper.find('.pager > a'),
        $forward = $pagerBox.find('a.forward'),
        $backward = $pagerBox.find('a.backward'),
        $from = $pagerBox.find('.from'),
        $to = $pagerBox.find('.to');

        $pagerButtons.unbind('click').click(page_articles);

        function page_articles(e) {
            //if (window.console) { console.log('======================================================='); }
            if (window.console) { console.log(e); }

            var element = (e.target || e.srcElement || e.originalTarget);
            //if (window.console) { console.log(element); }

            var $element = $(element);
            //if (window.console) { console.log($element); }

            var direction = $element.attr('data-direction');
            //if (window.console) { console.log('direction = ' + direction); }

            var current = parseInt($from.text(), 10);
            //if (window.console) { console.log('current = ' + current); }
            var last = parseInt($to.text(), 10);
            //if (window.console) { console.log('last = ' + last); }

            // set the next video
            var next = ((direction === 'forward') ? (current + 1) : (current - 1));

            //if (window.console) { console.log('next = ' + next); }

            // update the pager buttons
            $pagerButtons.addClass('disabled');
            if (next !== last) {
                $forward.removeClass('disabled');
            }
            if (next !== 1) {
                $backward.removeClass('disabled');
            }

            var $nextArticle = $featureArticles.eq(next-1);
            //if (window.console) { console.log($nextArticle); }

            //if (window.console) { console.log($nextArticle.find('.video_wrapper')); }
            lazyLoadVideo($nextArticle.find('.video_wrapper'), function() {
                //if (window.console) { console.log('callback'); }
                $featureArticles.removeClass('active');
                $nextArticle.addClass('active');

                // update the pager view
                $from.text(next);

                //if (window.console) { console.log($featureArticles); }
            });

            return true;
        }

        function lazyLoadVideo(wrapper, callback) {

            //if (window.console) { console.log(iframeElem); }

            var iframeSrc = wrapper.attr('data-video-url')
            //if (window.console) { console.log(iframeSrc); }

            // already done or no video URL given?
            if (! iframeSrc) {
                //if (window.console) { console.log('already done'); }

                if(typeof callback === 'function') {
                    callback();
                }

                return false;
            }

            // build an iframe object / element and append it to the video wrapper
            var iframe = $('<iframe class="to_be_lazy_loaded" width="441" height="270"></iframe>');
            iframe.attr('src', iframeSrc);
            //if (window.console) { console.log(iframe); }
            wrapper.append(iframe);

            wrapper.removeAttr('data-video-url');

            //if (window.console) { console.log(wrapper); }

            //iframeElem.attr('src', iframeElem.data('src'));
            //iframeElem.removeClass('to_be_lazy_loaded');

            if(typeof callback === 'function') {
                callback();
            }

            return true;
        }

        // lazy load the first e.g. active video
        lazyLoadVideo($('.feature-article.active').find('.video_wrapper'));
    },

    calcNotAssignedToGroupArticles: function() {
      var count = 0;
      $.each($('.fav-box'), function(){
        var box = $(this)
        var obj = box.data('groups');
        if(obj.length == 0) {
            count++;
        }
      });
      $('#not-assigned').html(count);
    },

    checkForSelectRemove: function(event, ui) {
        if($('body').hasClass('favorites_index')) {
            return;
        }
        $('.remove-criteria').remove();

         $.each($('.side-menu select'), function() {
            if($(this).multiselect('widget').find(':radio:eq(0):checked').length) {
                $(this).siblings('.remove-criteria').remove();
            }
            else if($(this).is('[multiple]') && $(this).multiselect("getChecked").length) {
                main.addSelectRemove($(this).parent());
            }
            else if($(this).is(':not([multiple])') && $(this).multiselect('widget').find(':radio:eq(0):checked').length == 0) {
                main.addSelectRemove($(this).parent());
            } else {
                $(this).siblings('.remove-criteria').remove();
            }
        });

        main.updateFloatingDivHeight();
    },

    addSelectRemove: function(appendToObj) {
        var rem = $('<span class="remove-criteria icon-icon_close_orange"></span>');
        $(appendToObj).append(rem);
        var func = function(){
            var select = $(this).siblings('select');
            select.find('option').removeAttr('selected');
            select.find('.all-opt').attr('selected', 'selected');
            if($(this).siblings('select:not([multiple])').length) {
                select.multiselect('widget').find(':radio:eq(0)').click();
            } else {
                select.multiselect('uncheckAll');
            }

            main.checkForSelectRemove();
        };
        rem.click(func);
        rem.bind('setback', func);
    },

    initSlider: function(parent) {
        if(typeof parent == 'undefined') {
            parent = $('body');
        }

        $.each(parent.find(".box-resource"), function() {
            if($(this).find('.ui-slider').length > 0) {
                return;
            }
            var radios = $(this).find(":radio");

            var div = $('<div></div>');

            if($(this).children('h3').length) {
                div.insertAfter($(this).children('h3'));
            } else {
                div.prependTo(this);
            }

            div.addClass('slide-box');

            $(this).find(":radio").hide();

            $(this).find(":radio").click(function(event) {
                main.acitvateRadio($(this), event);
            });

            var slide = $('<div class="slide"></div>');
            slide.slider({
              min: 0,
              max: radios.length-1,
              slide: function(event, ui) {
                  radios.eq(ui.value).parents('.box-resource').find('.resource label').hide();
                  radios.eq(ui.value).siblings('label').show();
              },
              change: function(event, ui) {

                  radios.eq(ui.value).trigger('click');

                  if (typeof event.originalEvent !== 'undefined') {
                    // for IE we trigger also change
                    if($.browser.msie) {
                        radios.eq(ui.value).trigger('change');
                    }
                  }

                  // set value class, when the slider value is other than the default
                  $(ui.handle).removeClass('ui-slider-has-value');
                  if ( ui.value > 0) {
                    $(ui.handle).addClass('ui-slider-has-value');
                  }
              }
            }).prependTo(div);

            // activate last radio
            if($(this).find(':radio:checked').length == 0) {
                if(!$('body').hasClass('article_edit')) {
                    main.acitvateRadio($(this).find(":radio").last());
                } else if($('body').hasClass('article_edit')) {
                    main.acitvateRadio($(this).find(":radio").first());
                }
            } else {
                //activte saved
                var radio = $(this).find(':radio:checked');
                main.acitvateRadio(radio);
                radio.parents('.box-resource').find('.slide').slider('value', radio.parent().index());
            }
        });

    },

    addToGroup: function(obj, articleid) {
        var group = $(obj).val();
        $.ajax({
          url: addToGroupUrl,
          dataType: 'json',
          type: 'post',
          data: {group: group, article: articleid},
          success: function(json) {
            // sidemenu item
            var count = parseInt($('#group-' + group).children('span').html());
            $('#group-' + group).children('span').html(++count);

            var favbox = $(obj).parents('.fav-box');
            favbox.find('.group_controls').show().find('.groups').show().find('.items').show().append('<span>' + $(obj).find(':selected').html() + '<a data-group="' + $(obj).val() + '" data-article="' + articleid + '" class="remove">X</a></span>');
            $(obj).find(':selected').attr('disabled', 'disabled');
            $(obj).find('option:first').attr('selected', 'selected');
            var favData = favbox.data('groups');
            favData.push(parseInt(group));
            favbox.data('groups', favData);
            main.calcNotAssignedToGroupArticles();
          }
        });
    },

    parseISO8601: function (dateStringInRange) {
      var isoExp = /^\s*(\d{4})-(\d\d)-(\d\d)\s*$/,
          date = new Date(NaN), month,
          parts = isoExp.exec(dateStringInRange);

      if(parts) {
        month = +parts[2];
        date.setFullYear(parts[1], month - 1, parts[3]);
        if(month != date.getMonth() + 1) {
          date.setTime(NaN);
        }
      }
      return date;
    },

    initSelectDatepickerDates: function(startDate, endDate) {
        startDate = main.parseISO8601(startDate);
        endDate = main.parseISO8601(endDate);

        var today = new Date();
        var currentMonth = parseInt($.datepicker.formatDate('m', (startDate < today ? today : startDate) ));
        for(loopTime = startDate.getTime(); loopTime <= endDate.getTime(); loopTime += 86400000)
        {
            var loopDay=new Date(loopTime);
            var day = parseInt($.datepicker.formatDate('d', loopDay));
            var month = parseInt($.datepicker.formatDate('m', loopDay));

            if(currentMonth == month) {
                $.each($(".ui-datepicker-calendar td:not(.ui-datepicker-unselectable)"), function(){
                    if($(this).find('a').html() == day) {
                        $(this).find('a').addClass('ui-state-active');
                    }
                });
            }
        }
        main.updateFloatingDivHeight();
    },

    acitvateRadio: function(radio, event) {
        radio.parents('.box-resource').find('.resource label').hide();
        radio.siblings('label').show();
    },

    updateFloatingDivHeight: function(force_update) {
        if(typeof force_update == 'undefined' && (main.isMobile || $("body").hasClass("backend") || $("body").hasClass("article_edit"))) {
            return false;
        }

        // set DIV height
        $('.box-content-with-menu .box-content').height('auto');

        if(!$("body").hasClass("article_show")) {
            $('.box-content-with-menu .side-menu').height('auto');
        }

        var h = $('.box-content-with-menu').outerHeight(true);
        if(!$("body").hasClass("article_show")) {
            $('.box-content-with-menu .side-menu').outerHeight(h);
        }
        $('.box-content-with-menu .box-content:visible').outerHeight(h);
    },

    initToggleForPlanning: function() {
        $.each($('.head.toggle-item'), function(){

            if($(this).parent().find('select').length == 0) {
                var toggleFunc = function(e, triggered) {

                    e.preventDefault();
                    e.stopPropagation();

                    if(!triggered && !$(this).hasClass('hideCat')) {
                        return;
                    }

                    $.each($('.head.toggle-item:not(.hideCat)'), function() {
                        $(this).toggleClass('hideCat');
                        $(this).find('.toggle').toggleClass('hideCat');
                        $(this).parents('.con:first').children(':not(.head)').toggle();
                    });


                    $(this).toggleClass('hideCat');
                    $(this).find('.toggle').toggleClass('hideCat');
                    $(this).parents('.con:first').children(':not(.head)').toggle();

                    if(e.originalEvent == 'undefined') {
                        $(this).parents('.con:first').children('.selected_values').toggle();
                    }

                    if(main.isMobile) {
                        $(window).scrollTop($('.toggle-item:not(.hideCat)').offset().top - 50);
                    }

                    main.updateFloatingDivHeight();
                };

                $(this).click(toggleFunc);

                $(this).trigger('click', true);
            }
        });
        $('.form-box:visible .head.toggle-item:first').click();
    },

    planningSelected: function() {
        $('.selected_values').html('');

        $.each($('.con :checked'), function() {
            var labelText = $(this).siblings('label').text();
            var sValues = $(this).parents('.con:first').find('.selected_values:first');
            sValues.append('<div>' + labelText + '</div>');
        });
    },

    planningSetDefaultOptionsSelected: function() {
        $.each($('.default-opt'), function(){
            if($(this).parent().parent().find('input:not(.default-opt):checked').length == 0) {
                $(this).attr('checked', 'checked');
                $(this).get(0).checked = true;
            }
        });
    },

    fixButtons: function() {
        return;
    },

    questionAnswers: [],

    question: function(forceLoad,qIndex) {

        var overlayId = '#question-overlay';
        if(typeof forceLoad === 'undefined') {
            forceLoad = false;
        }
        if(!qIndex) {
            qIndex = 0;
        }

        $('div.overlay').remove();
        main.questionAnswers = [];
        $.ajax({
          url: questionUrl,
          dataType: 'html',
          type: 'post',
          data: {forceLoad: forceLoad},
          success: function(data) {
              if($.trim(data) === '') {
                  return;
              }
              $('#wrapper').append(data);
              $('div.question').hide();
              $('#q-' + qIndex).show();

              main.initOverlay(overlayId, {onClose: function(){
                  /*
                  $.ajax({url: questionUrl,
                          dataType: 'html',
                          type: 'post',
                          data: {op: 'disable_ask'},
                          success: function(data) {}
                  });
                  */
              }});

              $(overlayId).find('a.button').click(function(e){
                  e.preventDefault();
                  var answer = $(this).data('answer');

                  $.ajax({url: questionUrl,
                          dataType: 'html',
                          type: 'post',
                          data: {op: 'answer', question: qIndex, answer: answer},
                          success: function(data) {
                              main.questionAnswers.push(answer);
                              qIndex++;
                              var $currentQuestion = $('#q-' + qIndex);
                              if($currentQuestion.length) {
                                  $('.question:visible').fadeOut(500, function(){
                                    $currentQuestion.fadeIn();
                                  });
                              } else {
                                  $('.question:visible').fadeOut(500, function(){
                                      $(overlayId).find('.close').click();
                                  });
                              }
                          }
                  });
              });
          }
        });
    }
};

$(document).ready(function(){
    main.init();
});

$.fn.addRemoveInputIcon = function(options) {

    return this.each(function() {
            var new_div = document.createElement('div');
            var new_label = document.createElement('label');
            $(new_label).html($(this).attr('title'));

            $(new_label).addClass('remove-input');
            $(this).addClass('remove-input');

            var ninput = $(this).clone(true);

            $(new_label).click(function() {
                $(this).parents('.row:first').find('.youtube-box').remove();
                $(this).siblings('input:first').val('');
                $(new_label).hide();
                $(this).siblings('input:first').focus();
            });

            $(ninput).keyup(function() {
                if(ninput.val() === '') {
                    $(new_label).hide();
                } else {
                    $(new_label).show();
                }
            });

            $(ninput).blur(function() {
                if(ninput.val() === '') {
                    $(new_label).hide();
                } else {
                    $(new_label).show();
                }
            });

            if ($(ninput).val() === '') {
                $(new_label).hide();
            }

            $(new_div).append(new_label);
            $(new_div).css({position: 'relative', display: 'inline-block', width: '100%'});

            $(new_div).append(ninput);

            $(this).replaceWith($(new_div));

    });
};

/*!
* jQuery Cookie Plugin
* https://github.com/carhartl/jquery-cookie
*
* Copyright 2011, Klaus Hartl
* Dual licensed under the MIT or GPL Version 2 licenses.
* http://www.opensource.org/licenses/mit-license.php
* http://www.opensource.org/licenses/GPL-2.0
*/
(function($) {
    $.cookie = function(key, value, options) {

        // key and at least value given, set cookie...
        if (arguments.length > 1 && (!/Object/.test(Object.prototype.toString.call(value)) || value === null || value === 'undefined')) {
            options = $.extend({}, options);

            if (value === null || value === 'undefined') {
                options.expires = -1;
            }

            if (typeof options.expires === 'number') {
                var days = options.expires, t = options.expires = new Date();
                t.setDate(t.getDate() + days);
            }

            value = String(value);

            return (document.cookie = [
                encodeURIComponent(key), '=', options.raw ? value : encodeURIComponent(value),
                options.expires ? '; expires=' + options.expires.toUTCString() : '', // use expires attribute, max-age is not supported by IE
                options.path ? '; path=' + options.path : '',
                options.domain ? '; domain=' + options.domain : '',
                options.secure ? '; secure' : ''
            ].join(''));
        }

        // key and possibly options given, get cookie...
        options = value || {};
        var decode = options.raw ? function(s) { return s; } : decodeURIComponent;

        var pairs = document.cookie.split('; ');
        for (var i = 0, pair; pair = pairs[i] && pairs[i].split('='); i++) {
            if (decode(pair[0]) === key) return decode(pair[1] || ''); // IE saves cookies with empty string as "c; ", e.g. without "=" as opposed to EOMB, thus pair[1] may be undefined
        }
        return null;
    };
})(jQuery);

Array.prototype.removeItem = function(v) {
    return $.grep(this, function(e) {
        return e !== v;
    });
};

/* App.js */
(function ($) {
	$.fn.wait = function (time, type) {
		time = time || 1000;
		type = type || "fx";
		return this.queue(type, function () {
			var self = this;
			setTimeout(function () {
				$(self).dequeue();
			}, time);
		});
	};
}(jQuery));

(function ($) {
	$.fn.msg = function (time, fade) {
		time = time || 4500;
		fade = fade || 500;
		$(this).wait(time).fadeOut(fade, function () {
			$(this).remove();
		});
	};
}(jQuery));

(function ($) {
	var self,
	steps,
	stepNavi,
	mobileStepNavi,
	settings = {
		activeClass: 'active',
		currentClass: 'current',
		steps: '#article_form_steps .step',
		stepNavi: '#article_form_navigation ul > li',
		mobileStepNavi: '#article_form_steps .step-head'
	};

	var methods = {
		init: function (options) {
			self = this;
			settings = $.extend(settings, options);

			steps = $(settings.steps);
			stepNavi = $(settings.stepNavi);
			mobileStepNavi = $(settings.mobileStepNavi);
			stepNavi.click(function (e) {
				e.preventDefault();
                main.updateFloatingDivHeight();
			});

			mobileStepNavi.click(function (e) {
				e.preventDefault();
			});

			methods.bindings();

			if(window.location.hash) {
				var hash = window.location.hash.replace('#', '');
				if(stepNavi.filter('#stepnavi-' + hash ).length) {
					methods.goToStep(stepNavi.index(stepNavi.filter('#stepnavi-' + hash)));
                    window.location.hash = '';
				}
			}

			return this;
		},

		bindings: function () {

			$('body').on('click', '#save', function(){
				self.bind('complete', function(){
					if(window.opener) {
						if($('#publish').is(':checked')) {
							window.opener.$('[data-linkedtype="expert"]').append('<option value="' + $('[name=id]').val() + '" selected="selected">' + $('[name="firstname"]').val() + ' ' + $('[name="lastname"]').val() + '</option>');
							window.opener.$('[data-linkedtype="expert"]').multiselect('refresh');
						}
						window.opener.focus();
						window.close();
					}
				});
			});

			self.bind('submit', function (e) {
				e.preventDefault();
				methods.nextStep();
                main.updateFloatingDivHeight();
			});

			self.on('click', 'button.last', function () {
				var $submitButton = $('<input type="hidden" name="save" value="' + $(this).val() + '"/>');
				methods.currentStep().append($submitButton);
			});

			self.on('click', settings.stepNavi + ':not(' + settings.currentClass + ')', function (e) {
				e.preventDefault();
				methods.goToStep($(this).index());
                main.updateFloatingDivHeight();
			});

			self.on('click', settings.mobileStepNavi + ':not(' + settings.currentClass + ')', function (e) {
				e.preventDefault();
				methods.goToStep(mobileStepNavi.index(this));
                main.updateFloatingDivHeight();
			});
		},

		stepCount: function () {
			return steps.length;
		},

		currentStep: function () {
			return steps.filter('.' + settings.currentClass);
		},

		nextStep: function () {
			var currentStep = steps.index(methods.currentStep());
			methods.goToStep(currentStep + 1);
		},

		goToStep: function (num) {

			var currentStep = methods.currentStep();

			currentStep.find('.linklist').each(function() {
				$(this).find('[type=text]:input:not(:first)').each(function() {
					if($(this).val() == '') {
						$(this).parents('.row').remove();
					}
				});
			});

			if (!methods.saveArea()) {
				return false;
			}

			currentStep.find('span.error').remove();

			if (num >= steps.length) {
				self.unbind('submit');
				self.submit();
				return;
			}

			stepNavi
				.removeClass(settings.currentClass) //.removeClass(settings.activeClass)
				.filter(':lt(' + (num + 1) + ')').addClass(settings.activeClass)
				.end().eq(num).addClass(settings.currentClass);

			mobileStepNavi
				.removeClass(settings.currentClass) //.removeClass(settings.activeClass)
				.filter(':lt(' + (num + 1) + ')').addClass(settings.activeClass)
				.end().eq(num).addClass(settings.currentClass);

			steps
				.removeClass(settings.currentClass)
				.eq(num).addClass(settings.currentClass);

			self.find('.indikator')
				.removeClass('step_1')
				.removeClass('step_2')
				.removeClass('step_3')
				.removeClass('step_4')
				.removeClass('step_5')
				.addClass('step_' + parseInt(num + 1,10));

            $('#article_form_navigation li').removeClass('done');
            $('#article_form_navigation li.current').prevAll().addClass('done');
		},

		saveArea: function () {
			var currentStep = methods.currentStep();

			var area = currentStep.data('area');
			var data = self.serialize();
			var success = false;

			var request = $.ajax({
				url: self.data('url'),
				type: 'POST',
				data: data + '&area=' + area,
				dataType: 'json',
				async: false,
				cache: false,
				context: currentStep
			});

			request.done(function (json) {
				if (json.success === true) {
					success = true;
				} else {
					this.replaceWith(json.stepHtml);
				}

				main.fixButtons();
				steps = $(settings.steps);

                self.trigger('complete', [json]);
			});

			return success;
		}
	};

	$.fn.articleForm = function (method) {
		if (methods[method]) {
			return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
		} else if (typeof method === 'object' || !method) {
			return methods.init.apply(this, arguments);
		} else {
			$.error('Method ' +  method + ' does not exist on jQuery.articleForm');
		}
	};

}(jQuery));

(function ($, window, document, undefined) {
    "use strict"
    var pluginName = "ajaxForm",
        pluginVersion = "0.1.0",
        Plugin = function (element, options, dataName) {
            var self = {
                element: element,
                $element: $(element),
                options: options,
                dataName: dataName,
                old_input: null,
                old: null
            },
            bindings = function () {
                self.$element.on('change', ':input', function (e) {
                    if (e.originalEvent !== 'undefined') {
                        window.location.hash = '-'
                    }
                    update(e)
                }).on('update', function (e, async, postData) {
                    update(e, async, postData)
                })
                self.$element.find('button[type=submit]').click(function (e) {
                    update(e);
                })
            },
            update = function (e, async, postData) {
                self.old_input = null
                if (e) {
                    var target = $(e.currentTarget)
                    if (target.is(':input[type=checkbox]') || target.is(':input[type=radio]') || target.is('select')) {
                        self.old_input = target
                    }
                    if (target.data('autosubmit') == false) {
                        return;
                    }
                }
                $.proxy(self.options.beforeLoad, self)()

                async = typeof async == 'undefined' ? true : async;

                if(self.$element.data('async') == 'true') {
                    async = true;
                } else if(self.$element.data('async') == 'false') {
                    async = false;
                }

                postData = typeof postData == 'undefined' ? false : postData;

                self.$element.mask(msgLoading);

                var dataType = self.$element.data('datatype') ? self.$element.data('datatype') : 'html'
                var request = $.ajax(self.$element.attr('action'), {
                    type: self.options.method,
                    cache: false,
                    context: self.$element,
                    async: async,
                    data: postData == false ? self.$element.serialize() : postData,
                    dataType: dataType,
                    success: replaceContent,
                    error: function (jqXHR, textStatus, errorThrown) {

                    }
                })
                request.done($.proxy(self.options.afterLoad, self.$element))
            },
            getReplaceSelector = function () {
                return self.$element.data('replace')
            },
            getFragmentSelector = function () {
                return self.$element.data('replaceFragment')
            },
            replaceContent = function (ajax_response) {
                self.$element.unmask();

                if (typeof ajax_response == 'object') {
                    self.$element.trigger('complete', [ajax_response, self.old_input])
                } else {
                    var div = $('<div></div>')
                    div.append($(ajax_response))
                    var selector = getReplaceSelector()
                    var fragmentSelector = getFragmentSelector()
                    var content = fragmentSelector ? $(ajax_response).find(fragmentSelector) : $(ajax_response)
                    var context = selector ? $(selector) : self.$element
                    if (ajax_response != '') {
                        context.each(function () {
                            $(this).replaceWith(content)
                        })
                    }
                    if (self.$element.parents('.overlay').length) {
                        if (ajax_response != '') {
                            main.initOverlay('#' + self.$element.parents('.overlay').attr('id'))
                            content.find('form.ajax-form').ajaxForm({
                                method: 'post'
                            })
                        }
                    }
                    self.$element.trigger('complete', [content.clone(), self.old_input])
                }
                main.updateFloatingDivHeight()
            },
            _callbackSupport = function (callback) {
                if ($.isFunction(callback)) {
                    callback.call(self.$element.data(dataName), self.$element)
                }
                return this
            },
            _events = function () {
                return this
            },
            getOption = function (key, callback) {
                return self.options[key] || undefined
            },
            getOptions = function (callback) {
                return self.options || undefined
            },
            setOption = function (key, value, callback) {
                if (typeof key === "string") {
                    self.options[key] = value
                }
                _callbackSupport(callback)
                return this
            },
            setOptions = function (newOptions, callback) {
                if ($.isPlainObject(newOptions)) {
                    self.options = $.extend({}, self.options, newOptions)
                }
                _callbackSupport(callback)
                return this
            },
            disable = function (callback) {
                _callbackSupport(callback)
                return this
            },
            enable = function (callback) {
                _callbackSupport(callback)
                return this
            },
            destroy = function (callback) {
                _callbackSupport(callback)
                return this
            },
            create = function (callback) {
                bindings()
                _callbackSupport(callback)
                return this
            }
            return {
                version: pluginVersion,
                self: self,
                getOption: getOption,
                getOptions: getOptions,
                setOption: setOption,
                setOptions: setOptions,
                disable: disable,
                enable: enable,
                destroy: destroy,
                create: create
            }
        }
    $.fn[pluginName] = function (options) {
        return this.each(function () {
            var element = $(this),
                plugin, dataName = pluginName,
                obj = {}
            if ($.data(element[0], dataName)) {
                return
            }
            options = $.extend({}, $.fn[pluginName].options, options)
            plugin = new Plugin(this, options, dataName).create()
            $.data(element[0], dataName, plugin)
            obj[pluginName] = function (elem) {
                return $(elem).data(dataName) !== undefined
            }
            $.extend($.expr[":"], obj)
        })
    }
    $.fn[pluginName].options = {
        method: 'GET',
        beforeLoad: $.noop,
        afterLoad: $.noop
    }
}(jQuery, window, document));

message = function(text, type, time, fade) {
	type = type || 'error';
	time = time || 4500;
	fade = fade || 1000;
	var msg = $('<div class="msg ' + type + '">' + text + '</div>');
	$('#messages').append(msg);
	msg.msg(time, fade);
};

var video_tpl = $('<div class="youtube-box"><img src="" alt="Youtube Video" /><div class="text"><strong></strong><div></div><a target="_blank" href="#"></a><label>' + (typeof descriptionText == 'undefined' ? '' : descriptionText) + '</label><input type="text"/></div></div>');

var addLink = function (e) {
	e.preventDefault();

    if($(this).parent().hasClass('videos')) {
        var row = $(this).parent().find('.row:last').clone();
        var key = parseInt(row.find('[name=key]').val())+1;
        row.find('[name=key]').val(key);
        row.find('.youtube-box, [name=desc]').remove();
        row.find('[type=url]').val('').attr('name', 'videos[' + key + '][url]');
        $(this).before(row);
    } else {
        var allLinks = $(".external_links span.input");
       	var maxIndex = 0;
       	$.each(allLinks, function(){
       		var idx = parseInt($(this).data("index"),10);
       		if (idx > maxIndex) {
       			maxIndex = parseInt($(this).data("index"),10);
       		}
       	});
       	var newIndex = maxIndex + 1;

       	var linkHtml = '' +
       		'<div class="row linkable">' +
       			'<span class="input first" data-index="0">' +
       				'<input type="url" name="external_links[##index##][url]" value=""/>' +
       				'<div><input type="checkbox" name="external_links[##index##][show_link]" checked="checked" id="cnew_##index##" /><label class="linkable" for="cnew_##index##">' + linkableText + '</label></div>' +
       			'</span>' +
       		'</div>';

       	//var new_link = $(this).parents('.linklist').find('.row:first').clone(true);
       	var new_link = $(linkHtml.replace(/##index##/g,newIndex));
       	new_link.find('input').val('');
       	new_link.find('span.first')
       		.removeClass('first')
       		.removeAttr("data-index")
       		.data("index",newIndex);
       	new_link.find('label:not(.linkable), .youtube-box').remove();
       	$(this).before(new_link);
       	$(this).parents('.form-box').hide().show();
    }

    main.updateFloatingDivHeight();
};

var onSubmit = function(e) {
	e.preventDefault();

    var dataType = $(this).data('datatype') ? $(this).data('datatype') : 'html';

    $(this).mask(msgLoading);

	var request = $.ajax($(this).attr('action'), {
		data: $(this).serialize(),
		type: 'POST',
		dataType: dataType,
		async: false,
		cache: false,
		context: this
	});

	request.done(function(html) {

        if(typeof html == 'object') {
            $(this).trigger('complete', [html]);
        }
		else if(html === 'true') {
			window.location.reload();
		} else {
            $(this).unmask();
			var $form = $(html).find('form');
			$(this).replaceWith($form);
			main.fixButtons();

			if(main.isMobile && $form.length) {
				$form.submit(onSubmit);
			}
		}
	});
};

$('form#article').articleForm();
$('#messages .msg').msg();

$('form.ajax-form').ajaxForm({
    beforeLoad: function () {
        try {
            if(this.$element.find('#result_view_list').length) {
                $('#result_view_list').html('<div class="ajax-loader"></div>');
            }
            if(main.isMobile) {
                $(".main-box").mask(msgLoading);
            }
        } catch(e) {}
    },
    afterLoad: function () {
        $(".main-box").unmask();
    }
});


$('body').on('click', '.form-box a.add-link', addLink);

var removeFavoriteFunction = function(e) {
	e.preventDefault();
    e.stopPropagation();

    if($(this).hasClass('disabled')) {
        return;
    }

    main.lastEventTarget = $(e.target).parent('.button').length ? $(e.target).parent().get(0) : e.target;

	$(".main-box").mask(msgLoading);

	var request = $.ajax($(this).attr('href'), {
		type: 'GET',
		cache: false,
		context: this,
		dataType: 'json'
	});

	request.error(function(){
        try {
            $(".main-box").unmask();
        } catch(e) {}
	});
	// Favorites Toggle
	request.done(function(data){
		if(data && data.success) {
			var counterElement = $('.content-head a.fav span'),
				count = parseInt(counterElement.text(),10),
				newCount = 0;

			if(data.action != 'add') {
				$(this).removeClass('active');

                $(this).unbind('click');
				$(this).click(function(event){
                    event.preventDefault();
                    event.stopPropagation();
                    main.showOverlay($(this).data('rel'), $(this).data('href'));
                });

				counterElement.text(count - 1);
                newCount = parseInt($('#fav-count').text(), 10) -1;
				$('#fav-count').text(newCount);
			}
            if(main.isMobile && count > 99) {
                $('#fav-count').parent().css({'text-ident': '-10000px', 'padding': '0'});
            } else if(main.isMobile) {
                $('#fav-count').parent().css({'text-ident': '0', 'padding': '15px 12px 12px 40px'});
            }
		} else {
            window.location.reload();
            return;
        }

		if(data.messages && data.messages.length) {
			$.each(data.messages, function() {
				message(this.text, this.type, 4500, 500);
			});
		}

		$(".main-box").unmask();
	});
};
// Favorites toggle
$('body').on('click', '.main-box a.fav.active:not(.img-button), a.fav-remove, .fav-button.active', removeFavoriteFunction);
/*
	$('body').on('click', '.linklist a.remove', function () {
		$(this).parents('.linklist_item').remove();
	});
*/

$('body').on('click', '#favorit_results .side-menu li a', function (e) {
	e.preventDefault();
	if($(this).parents('li').hasClass('current')) {
		return false;
	}
	$(this).parents('.side-menu').find('li.current').removeClass('current');
	$(this).parents('li').addClass('current');
	$(this).parents('#favorit_results').find('.box-content').hide().filter($(this).attr('href')).show();
	return false;
});

// Confirm Links
$('body').on('click', 'a[data-confirm], button[data-confirm]', function() {
	return confirm($(this).data('confirm'));
});

// Msg Links
$('body').on('click', 'a[data-msg], button[data-msg]', function() {
	return alert($(this).data('msg'));
});

// Video Inputs
$('body').on('blur', 'input.video', function(e) {
	$(this).parents('.row').find('.youtube-box').remove();
	if($(this).val()) {
		var video_url = $(this).val();
		var request = null;
        var $hiddenDescription = $(this).parents('.row').find('[name=desc]').val();
        var key = $(this).parents('.row').find('[name=key]').length ? parseInt($(this).parents('.row').find('[name=key]').val()) : 0;

		// Check vimeo url
		var match = video_url.match(/^http:\/\/vimeo\.com\/(\d+)$/i);

		if(match) {
			request = $.ajax("http://vimeo.com/api/v2/video/" + match[1] + ".json?callback=?", {
				dataType: 'jsonp',
				type: 'GET',
				context: $(this).parents('.row')
			});

			request.done(function(data){
				if(data[0]) {
					data = data[0];
					var video_html = video_tpl.clone();
					video_html.find('img').attr('src', data.thumbnail_small).attr('alt', data.title);
					video_html.find('a').attr('href', data.url);
					video_html.find('strong').html(data.title);
                    video_html.find('input[type=text]').val($hiddenDescription);
                    video_html.find('input[type=text]').attr('name', 'videos[' + key + '][description]');

					video_html.find('.text div').html(data.description.substr(0, 50) + (data.description.length > 50 ? '...' : ''));
					$(this).append(video_html);
				}
			});

			return;
		}

		// Check youtube url
		match = video_url.match(/^(http|https):\/\/www\.youtube\.com\/watch.*?v=([a-zA-Z0-9\-_]+).*$/i);

		if(match) {
			request = $.ajax("http://gdata.youtube.com/feeds/api/videos/" + match[2] + "?v=2&alt=jsonc&prettyprint=true&callback=?", {
				dataType: 'jsonp',
				type: 'GET',
				context: $(this).parents('.row')
			});

			request.done(function(json){
				if(json) {
					var video_html = video_tpl.clone();
					video_html.find('img').attr('src', json.data.thumbnail.sqDefault).attr('alt', json.data.title);
					video_html.find('a').attr('href', 'http://www.youtube.com/watch?v=' + json.data.id);
					video_html.find('strong').html(json.data.title);
                    video_html.find('input[type=text]').val($hiddenDescription);
                    video_html.find('input[type=text]').attr('name', 'videos[' + key + '][description]');
					video_html.find('.text div').html(json.data.description.substr(0, 50) + (json.data.description.length > 50 ? '...' : ''));
					$(this).append(video_html);
				}
			});

			return;
		}
	}
});

if($('form#article').length) {

    (function( $ ) {
      $.fn.createUploader = function(options) {
          var settings = $.extend( {
             'url'         : uploadurl,
             'param' : ''
           }, options);

          this.each(function() {
            var self = $(this);
            var filters = self.attr('id');
            filters = eval(filters);

            // Upload
            var uploader = new plupload.Uploader({
                runtimes : 'gears,html5,flash,silverlight,browserplus',
                browse_button : self.find('.add-link:first').attr('id'),
                container: self.attr('id'),
                max_file_size : '100mb',
                url : settings.url + '?' + settings.param,
                flash_swf_url : 'js/libs/plupload/plupload.flash.swf',
                silverlight_xap_url : 'js/libs/plupload/plupload.silverlight.xap',
                filters : filters
            });

            uploader.init();

            uploader.bind('FilesAdded', function(up, files) {

                for (var i in files) {
                    if(files[i].id) {
                        var cloned = self.children('.new_file:first').clone();
                        self.find('.filelist-items').append(cloned);
                        var new_file = cloned.attr('id', files[i].id).show();
                        new_file.find('.new_file_name').text(files[i].name + ' (' + plupload.formatSize(files[i].size) + ')');
                    }
                }
                uploader.start();
            });

            uploader.bind('UploadProgress', function(up, file) {
                var item = $('#' + file.id);
                item.find('.new_file_progress_indicator').css('width', file.percent + "%");
                item.find('.new_file_progress_percent').text(file.percent + "%");
            });

            uploader.bind('Error', function(up, error) {
                alert(error.message);
            });

            uploader.bind('FileUploaded', function(up, file, response) {
                var item = $('#' + file.id);
                var json = $.parseJSON( response.response );
                item.replaceWith(json.html);
            });
           });
      };
    })( jQuery );

    $('.external_links.filelist').createUploader();
    $('.external_links.logo').createUploader({param: 'logo=1'});
}

$('body').on('click', '.article_linked_list a.remove', function(e) {
	e.preventDefault();
	$(this).parents('li').remove();
	return false;
});

// Login Overlay
$('body').on('click', '#removeacc-overlay form button', function() {
	$(this).parents('form').append('<input type="hidden" name="' + $(this).attr('name') + '" value="' + $(this).attr('value') + '"/>');
	return true;
});

$('body').on('submit', '#ask-overlay form, #reg-overlay form, #login-overlay form, #fav-overlay form, #profile-overlay form, #newgroup-overlay form, #lostpassword-overlay form, #removeacc-overlay form', onSubmit);

if($.browser.msie) {
	$('body').on('keypress', '#ask-overlay form input, #reg-overlay form input, #login-overlay form input, #fav-overlay form input, #profile-overlay form input, #newgroup-overlay form input, #lostpassword-overlay form input, #removeacc-overlay form input', function(e){
		if (e.which == '13') {
	       $(this).parents('form').submit();
	    }
	});
}


$('body').on('change', 'select#form_end_month', function(e) {
	if($(this).val() == '0') {
		$('select#form_end_year').attr('disabled', 'disabled').val('');
	} else {
		$('select#form_end_year').removeAttr('disabled');
	}
});

$('body').on('click', 'a.remove_img', function(e) {
	e.preventDefault();
	$(this).parents('.uploaditem-box').remove();
});

$('input.video').blur();

$('a.deselect_radio').click(function(e) {
	e.preventDefault();
	$(this).parents('.hr-line').find(':input[type=radio]').prop('checked', false);
	$(this).parents('form').trigger('update');
	return false;
});

/*
    Berechnung der Breiten der Hauptnavigations-Items
*/
function set_main_navigation_widths() {

    $main_navigation = $('.content-head').find('.arrow-bg > ul');
    //if (window.console) { console.log($main_navigation); }

    if ( ! $main_navigation.length) {
        return false;
    }

    $main_navigation_items = $main_navigation.find('> li:not(.clear)');
    $main_navigation_first_item = $main_navigation.find('> li:first');
    //if (window.console) { console.log($main_navigation_items); }

    if ( ! $main_navigation_items.length) {
        return false;
    }

    main_navigation_width = parseInt($main_navigation.outerWidth(true) - $main_navigation_first_item.outerWidth(true), 10);
    //if (window.console) { console.log('main_navigation_width = ' + main_navigation_width); }

    main_navigation_items_width_new = parseInt( (main_navigation_width) / ($main_navigation_items.length-1), 10) - 1;
    //if (window.console) { console.log('main_navigation_items_width_new = ' + main_navigation_items_width_new); }

	$main_navigation.find('> li:not(:first):not(.clear)').css('width', main_navigation_items_width_new);

    // beauty, beauty, beauty: add 1px to the last navigation item
    $main_navigation_items.last().css('width', main_navigation_items_width_new + 1);

    return true;
}

if ( ! $('body').hasClass('mobile')) {
    set_main_navigation_widths();
	var mTimeout = 0;
	var headTimeout = 0;
	$('.arrow-bg > ul > li.deepen:not(.active)').hover(function(e){
		e.preventDefault();
		clearTimeout(mTimeout);
		clearTimeout(headTimeout);
		var $ul = $(this).find('ul');
		$ul.show();
		$ul.find('li').unbind('hover').hover(function(){
			clearTimeout(mTimeout);
			clearTimeout(headTimeout);
		});

		$(this).siblings().unbind('hover').hover(function(){
			$(this).unbind('hover');
			clearTimeout(mTimeout);
			mTimeout = setTimeout(function(){
				$ul.fadeOut();
			}, 1000);
		});
	});
	if($('li.deepen.active').length == 0) {
		$('.arrow-bg > ul').mouseout(function(){
			$this = $(this);
			clearTimeout(headTimeout);
			headTimeout = setTimeout(function(){
				$this.find('ul').fadeOut();
			}, 1000);
		});
	}
	$('.deepen > a').click(function(e){e.preventDefault();});
}

/* End */