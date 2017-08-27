define(function(require, exports, module) {
	var Notify = require('common/bootstrap-notify');

	exports.run = function(options) {
		require('./header').run();
		var $table = $('#learning-data-table');

		$table.on('click', '.publish-course', function(){
			if (!confirm('您确认要发布此产品吗？')) return false;
			$.post($(this).data('url'), function(html){
				var $tr = $(html);
				$table.find('#' + $tr.attr('id')).replaceWith(html);
				Notify.success('产品发布成功！');
			});
		});
	};

});
