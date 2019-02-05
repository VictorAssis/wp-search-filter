(function($) {
    //init
    var params = wp.params;
    if (typeof params.f === "undefined")
        params.f = {};

    var add_param = function(key, value) {
        if (typeof params.f[key] === "undefined")
            params.f[key] = [];
        params.f[key].push(value);
        update_results();
    }

    var remove_param = function(key, value) {
        if (typeof params.f[key] !== "undefined") {
            var index = params.f[key].indexOf(value);
            if (index > -1)
                params.f[key].splice(index, 1);
            if (params.f[key].length == 0)
                delete params.f[key];
        }
        update_results();
    }
    
    var clear_params = function() {
        params.f = {};
        update_results();
    }

    var update_results = function() {
        show_loading();
        //Update URL
        var paramsTmp = $.extend(true, {}, params);
        for (var key in paramsTmp.f) {
            // skip loop if the property is from prototype
            if (!paramsTmp.f.hasOwnProperty(key)) continue;
        
            //implode options
            paramsTmp.f[key] = paramsTmp.f[key].join(",");
        }
        var url = "?" + $.param( paramsTmp );
        window.history.pushState("", "", url);

        //Update results
        $.get(document.URL,'',function(data){
            $(".site-content").html($(data).find(".site-content"));
            hide_loading();
            toggle_reset();
        },'html');
    }

    var show_loading = function () {
        $(".wpfilterfield-loading").addClass('loading');
    }

    var hide_loading = function () {
        $(".wpfilterfield-loading").removeClass('loading');
    }

    var toggle_reset = function () {
        if (Object.keys(params.f).length)
            $(".wpfs-reset-filter").addClass('active');
        else
            $(".wpfs-reset-filter").removeClass('active');
    }

    // change
    $(document).ready(function(){
        //add loading
        $('body').prepend("<div class='wpfilterfield-loading'></div>");
        
        //explode itens
        for (var key in params.f) {
            // skip loop if the property is from prototype
            if (!params.f.hasOwnProperty(key)) continue;
        
            //explode options
            params.f[key] = params.f[key].split(",");
    
            //check options
            for (let i = 0; i < params.f[key].length; i++) {
                $('.wpfs-filter-field input[type="checkbox"][name="'+key+'[]"][value="'+params.f[key][i]+'"]').attr('checked','checked');
            }
        }

        //Show reset button
        toggle_reset();

        $('.wpfs-filter-field').on('change', 'input[type="checkbox"]', function(){
            var name = $(this).attr('name').replace('[]','');
            var value = $(this).attr('value');
            if ($(this).is(':checked'))
                add_param(name,value);
            else
                remove_param(name,value);
        });

        $('body').on('click','#wpfs-reset-filter-button', function(){
            clear_params();
        });
	});

})(jQuery);