define(function(require, exports, module) {

    var AutoComplete = require('autocomplete');
    var DynamicCollection = require('../widget/dynamic-collection4');
    var Notify = require('common/bootstrap-notify');
    require('jquery.sortable');

    exports.run = function() {

    	require('./header').run();
    	
        var dynamicCollection = new DynamicCollection({
            element: '#teachers-form-group',
            onlyAddItemWithModel: true,
            beforeDeleteItem: function(e){
            	var teacherCounts=$("#teacher-list-group").children("li").length;
	            if(teacherCounts <= 1){
	                Notify.danger("产品至少需要一个商家！");
	                return false;
	            }
	            return true;
            }
        });

	    var autocomplete = new AutoComplete({
	        trigger: '#teacher-input',
	        dataSource: $("#teacher-input").data('url'),
	        filter: {
	            name: 'stringMatch',
	            options: {
	                key: 'nickname'
	            }
	        },
            selectFirst: true
	    }).render();

	    autocomplete.on('itemSelect', function(data){
	    	var error = '';
	    	dynamicCollection.element.find('input[name="ids[]"]').each(function(i, item) {
	    		if (parseInt(data.id) == parseInt($(item).val())) {
	    			error = '该商家已添加，不能重复添加！';
	    		}
	    	});

	    	if (error) {
	    		Notify.danger(error);
	    		dynamicCollection.clearInput();
	    	} else {
		    	dynamicCollection.addItemWithModel(data);
	    	}
		});

        dynamicCollection.on('beforeAddItem', function(value) {
            autocomplete.set('inputValue', null);
            autocomplete.setInputValue(value);
        });

		$(".teacher-list-group").sortable({
			'distance':20
		});


    };

});