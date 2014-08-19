define(['jquery'], function($) {
		return {
				filterList: '.js-filter',
				inputTarget: '.js-filter__input',
				toggleBtn: '.hide-btn',
				hideContent: '.hide-box',
				init: function() {
					this.toggle();

					if (this.filterList.length !==0){
							this.filterRepos();
					}
				},
				filterRepos: function(){
						var repoList = $(this.filterList + '> li');
						var searchField = $(this.inputTarget);
						var _this = this;

						searchField.keyup(function(){
								var searchVal = searchField.val().toLowerCase();
								_this.delay(function(){
										repoList.each(function(){
												var txt = $(this).text().toLowerCase();

												$(this).toggle(txt.indexOf(searchVal) === 0);
										});
								}, 500);
						});
				},
				toggle: function(){
						var btn = $(this.toggleBtn);
						var box = $(this.hideContent);

						btn.click(function(){
								box.slideToggle("slow");
						});
				},
				delay: function(callback, ms) {
						var timer = 0;
						clearTimeout(timer);
						timer = setTimeout(callback, ms);
				},
		};
});
