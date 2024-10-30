/**
 * legal-news-headlines.js - Javascript for the widget admin page.
 *
 * @package Legal News Headlines
 */


// admin interface javascript (for validation purpoases)
var legal_news_headlines_admin = {};

legal_news_headlines_admin.init = function() {
	jQuery('.legal_news_headlines_admin_list .parent_block input:checkbox').click( legal_news_headlines_admin.checkboxOnClick );
};

legal_news_headlines_admin.checkboxOnClick = function(e) {
	var $this = jQuery(e.target);
	console.log(1);
	var isParent = $this.hasClass('parent');
	var isChecked = $this.is(':checked');

	if (isParent) {
		if (isChecked)
			$this.parent().children('.child:checkbox').attr('checked', 'checked');
		else
			$this.parent().children('.child:checkbox').removeAttr('checked');
	}
	else {
		if (isChecked) {
			if ( $this.parent().children('.child:checkbox').size()===$this.parent().children('.child:checkbox:checked').size() )
				$this.parent().children('.parent:checkbox').attr('checked', 'checked');
		}
		else
			$this.parent().children('.parent:checkbox').removeAttr('checked');
	}
};