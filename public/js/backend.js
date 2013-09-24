var Backend = {};

// contains all tables returned from .dataTable()
Backend.tables = [];

$(document).ready(function(){
    $.getJSON("backend/texts", null, function(data){
        Backend.texts = data;
        Backend.init();
        Backend.initColumnFilter();
        Backend.initCkeditor();
    });
    Backend.initButtons();
});


Backend.init = function() {

    var $dataTables = $(".data-table");
    if($dataTables) {
        $dataTables.each(function(index,table){

            var $header = $(table).find("thead th");
            var columns = [];
            $header.each(function(){
                var obj = {};
                var thetype = $(this).data("dttype");
                if(thetype !== undefined) {
                    obj.sType = thetype;
                }
                columns.push(obj);
            });

            var oTable = $(table).dataTable( {
                //"sDom": 'R&lt;"H"lfr&gt;t&lt;"F"ip&lt;',
                "bJQueryUI": true,
                "aoColumns": columns,
                "sPaginationType": "full_numbers",
                "oLanguage": Backend.texts,
                "bStateSave": true,
                "fnStateSave": function (oSettings, oData) {
                    if(typeof window.localStorage === "object" && !!window.JSON && !!JSON.stringify) {
                        localStorage.setItem('DataTables_' + window.location.pathname, JSON.stringify(oData));
                    }
                },
                "fnStateLoad": function (oSettings) {
                    if(typeof window.localStorage === "object" && !!window.JSON && !!JSON.parse) {
                        return JSON.parse(localStorage.getItem('DataTables_' + window.location.pathname));
                    }
                }
            });

            Backend.tables.push(oTable);
        });
    }
    $('a.sticky').click(function(e){
        e.preventDefault();
        var $a = $(this);
        $.ajax({
            url: $(this).attr('href'),
            dataType: 'html',
            type: 'get',
	        complete: function(data) {
                $a.addClass('active');
            }
        });
    });
	$( "#sortable1, #sortable2" ).sortable({
	   connectWith: ".connectedSortable",
		receive: function(event, ui) {
			var $item = $(ui.item).find('[type=hidden]');
			if($(this).attr('id') == 'sortable2') {
				$item.attr('name', $item.attr('dataid'));
			} else {
				$item.removeAttr('name');
			}
		}

	 }).disableSelection();
	$( ".accordion" ).accordion({ heightStyle: "content", collapsible:true, active:false });
	$('.accordion-open').click(function (e) {
		 e.preventDefault();
	     $('.ui-accordion-header').removeClass('ui-corner-all').addClass('ui-accordion-header-active ui-state-active ui-corner-top').attr({'aria-selected':'true','tabindex':'0'});
	     $('.ui-accordion-header .ui-icon').removeClass('ui-icon-triangle-1-e').addClass('ui-icon-triangle-1-s');
	     $('.ui-accordion-content').addClass('ui-accordion-content-active').attr({'aria-expanded':'true','aria-hidden':'false'}).show();
	 });
	 $('.accordion-close').click(function (e) {
		 e.preventDefault();
	     $('.ui-accordion-header').removeClass('ui-accordion-header-active ui-state-active ui-corner-top').addClass('ui-corner-all').attr({'aria-selected':'false','tabindex':'-1'});
	     $('.ui-accordion-header .ui-icon').removeClass('ui-icon-triangle-1-s').addClass('ui-icon-triangle-1-e');
	     $('.ui-accordion-content').removeClass('ui-accordion-content-active').attr({'aria-expanded':'false','aria-hidden':'true'}).hide();
	 });
	$('.accordion-open').click();

	// check backend configurations to toggle selection
	if($('#add_expert\\.expert\\.active').is(':checked')) {
		$('#add_expert\\.global\\.active').removeAttr('checked').attr('disabled', 'disabled');
		$('#add_expert\\.global\\.active').siblings('input').val('false');
	}
	$('#add_expert\\.expert\\.active').click(function(){
		if($(this).is(':checked')) {
			$('#add_expert\\.global\\.active').removeAttr('checked').attr('disabled', 'disabled');
			$('#add_expert\\.global\\.active').siblings('input').val('false');
		} else {
			$('#add_expert\\.global\\.active').removeAttr('disabled');
			$('#add_expert\\.global\\.active').siblings('input').val('true');
		}
	});
	var checkConfigFunc = function() {
		if($('#module\\.videobox').is(':checked') && ($('#module\\.newsbox').is(':checked') || $('#module\\.eventbox').is(':checked'))) {
			$('.mobile-condition-options').show();
			if($('.mobile-condition-options input:checked').length == 0) {
				$('.mobile-condition-options input:first').attr('checked', 'checked');
			}
		} else {
			$('.mobile-condition-options').hide();
			$('.mobile-condition-options input').removeAttr('checked', 'checked');
		}
	};
	checkConfigFunc();

	$('#module\\.videobox, #module\\.newsbox, #module\\.eventbox').click(checkConfigFunc);
};

$('input[name=featured]').click(function(){
    var featured = $(this).is(':checked');

    $.ajax({
        url: $(this).data('url'),
        dataType: 'html',
        data: {featured: featured, video: $(this).data('video')},
        type: 'get',
	    complete: function(data) {
        }
    });
});

Backend.initColumnFilter = function() {
    var numTables = Backend.tables.length;

    for(var i = 0; i < numTables; i++) {
        var table = Backend.tables[i];
        table.find("tfoot td").each( function ( i ) {
            // reset filter
            //table.fnFilter('', i);
            if($(this).hasClass("filter")) {
                this.innerHTML = fnCreateSelect( table.fnGetColumnData(i,true,false) );

                var currentFilter = table.fnSettings().aoPreSearchCols[i].sSearch;
                if(currentFilter) {
                    $('select', this).find("option[value='"+currentFilter+"']").prop("selected","selected");
                }

                $('select', this).change( function () {
                    var $table = $(this).closest("table");
                    var oTable = $table.dataTable();
                    oTable.fnFilter( $(this).val(), i );
                });
            }
        });
    }
};

Backend.initButtons = function() {
    var $deleteButtons = $(".delete-button");

    if($deleteButtons.length) {
        var onClickDelete = function(){
            var question = $(this).data("question");
            var answer = confirm(question);
            // follow href link only if user confirms deletion
            // delete via GET is 'ok' as we are in a closed environment
            return answer;
        };

        $deleteButtons.each(function(index, button){
            $(button).click(onClickDelete);
        });
    }
};

Backend.initCkeditor = function() {
    $('.init-ckeditor-on-me').ckeditor({toolbar: [
       [ 'Link','Unlink','Anchor'  ],
       '/',
       ['Image'],
       '/',
       ['FontSize', 'Bold','Italic','Underline','StrikeThrough','-','Undo','Redo','-','Cut','Copy','Paste','Find','Replace','-','Outdent','Indent','-','Print'],
       '/',
       ['NumberedList','BulletedList','-','JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock']],
        filebrowserUploadUrl  :'/backend/pages/imgupload'});
};

/**
 * datatables extension for column filtering
 * @link http://www.datatables.net/release-datatables/examples/api/multi_filter_select.html
 */

var numHiddenSort = function(a,b) {
    var x = 0;
    var y = 0;
    $(a).each(function(){
        if($(this).hasClass("hidden-for-ordering")) {
            x = parseFloat($(this).text(),10);
        }
    });
    $(b).each(function(){
        if($(this).hasClass("hidden-for-ordering")) {
            y = parseFloat($(this).text(),10);
        }
    });
    return ((x < y) ? -1 : ((x > y) ?  1 : 0));
};
jQuery.fn.dataTableExt.oSort['num-hidden-asc']  = function(a,b) {
    return numHiddenSort(a,b);
};

jQuery.fn.dataTableExt.oSort['num-hidden-desc'] = function(a,b) {
    return numHiddenSort(b,a);
};

/**
 * Sorting function for german dates (dd.mm.yyyy)
 */
var dateDeSort = function(a,b) {
    var ukDatea = a.split('.');
    var ukDateb = b.split('.');

    var x = (ukDatea[2] + ukDatea[1] + ukDatea[0]) * 1;
    var y = (ukDateb[2] + ukDateb[1] + ukDateb[0]) * 1;

    return ((x < y) ? -1 : ((x > y) ?  1 : 0));
};
jQuery.fn.dataTableExt.oSort['de_date-asc']  = function(a,b) {
    return dateDeSort(a,b);
};

jQuery.fn.dataTableExt.oSort['de_date-desc'] = function(a,b) {
    return dateDeSort(b,a);
};

(function($) {
    /*
     * Function: fnGetColumnData
     * Purpose:  Return an array of table values from a particular column.
     * Returns:  array string: 1d data array
     * Inputs:   object:oSettings - dataTable settings object. This is always the last argument past to the function
     *           int:iColumn - the id of the column to extract the data from
     *           bool:bUnique - optional - if set to false duplicated values are not filtered out
     *           bool:bFiltered - optional - if set to false all the table data is used (not only the filtered)
     *           bool:bIgnoreEmpty - optional - if set to false empty values are not filtered from the result array
     * Author:   Benedikt Forchhammer <b.forchhammer /AT\ mind2.de>
     */
     $.fn.dataTableExt.oApi.fnGetColumnData = function ( oSettings, iColumn, bUnique, bFiltered, bIgnoreEmpty ) {
        // check that we have a column id
        if ( typeof iColumn == "undefined" ) return [];

        // by default we only wany unique data
        if ( typeof bUnique == "undefined" ) bUnique = true;

        // by default we do want to only look at filtered data
        if ( typeof bFiltered == "undefined" ) bFiltered = true;

        // by default we do not wany to include empty values
        if ( typeof bIgnoreEmpty == "undefined" ) bIgnoreEmpty = true;

        // list of rows which we're going to loop through
        var aiRows;

        if (bFiltered === true) {
            // use only filtered rows
            aiRows = oSettings.aiDisplay;
        } else {
            // use all rows
            aiRows = oSettings.aiDisplayMaster;
        }

        // set up data array
        var asResultData = [];

        for (var i=0,c=aiRows.length; i<c; i++) {
            iRow = aiRows[i];
            var aData = this.fnGetData(iRow);
            var sValue = aData[iColumn].replace( /<.*?>/g, "" );

            if (bIgnoreEmpty === true && sValue.length === 0) {
                // ignore empty values
                continue;
            } else if (bUnique === true && jQuery.inArray(sValue, asResultData) > -1) {
                // ignore unique values
                continue;
            } else {
                // else push the value onto the result data array
                asResultData.push(sValue);
            }
        }

        return asResultData;
    };
}(jQuery));

function fnCreateSelect( aData ) {
    var r='<select><option value="">Filter by...</option>', i, iLen=aData.length;
    for ( i=0 ; i<iLen ; i++ ) {
        r += '<option value="'+aData[i]+'">'+aData[i]+'</option>';
    }
    return r+'</select>';
}