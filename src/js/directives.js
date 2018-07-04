(function () {
	'use strict';
	
	var mod = angular.module('dotaStats')

	mod.directive('item', ['$items', function($items) {
		return {
			link: function(scope, element, attrs) {
				var item = $items.get(scope.item_id);
				scope.name = item.name;
				scope.localized_name = item.localized_name;
				scope.position = scope.pos || 'right';
				element.css('background-image', 'url(img/items/empty_lg.png)');
				element.css('background-repeat', 'no-repeat');
			},
			scope: {
				item_id: '=itemId',
				pos: '@position'
			},
			template: '<img popover="{{localized_name}}" popover-title="" popover-placement="{{position}}" popover-trigger="mouseenter" popover-animation="false" popover-append-to-body="true" ng-src="img/items/{{name}}_lg.png" alt="{{localized_name}}" title="{{localized_name}}" />'
		};
	}])

	mod.directive('hero', ['$heroes', function($heroes) {
		return {
			link: function(scope, element, attrs) {
				var hero = $heroes.get(scope.hero_id);
				scope.name = hero.name;
				scope.localized_name = hero.localized_name;
				
				if (scope.body === "" || scope.body === undefined || scope.body === null) {
					scope.pop_body = hero.localized_name;
					scope.pop_title = "";
				} else {
					scope.pop_body = scope.body
					scope.pop_title = hero.localized_name;
				}
				
				scope.position = scope.pos || 'right';
				element.css('background-image', 'url(img/heroes/empty_sb.png)');
				element.css('background-repeat', 'no-repeat');
			},
			scope: {
				hero_id: '=heroId',
				pos: '@position',
				body: '=body'
			},
			template: '<img popover="{{pop_body}}" popover-title="{{pop_title}}" popover-placement="{{position}}" popover-trigger="mouseenter" popover-animation="false" popover-append-to-body="true" ng-src="img/heroes/{{name}}_sb.png" alt="{{localized_name}}" title="{{localized_name}}" />'
		};
	}])

	mod.directive('unit', ['$units', function($units) {
		return {
			link: function(scope, element, attrs) {
				var unit = $units.get(scope.unit_name);
				if (unit == undefined) {
					return;
				}
				scope.name = unit.name;
				scope.localized_name = unit.localized_name;
				
				scope.pop_body = unit.localized_name;
				scope.pop_title = "";
				
				scope.position = scope.pos || 'right';
				element.css('background-image', 'url(img/units/empty_sb.png)');
				element.css('background-repeat', 'no-repeat');
			},
			scope: {
				unit_name: '=unitName',
				pos: '@position'
			},
			template: '<img popover="{{pop_body}}" popover-title="" popover-placement="{{position}}" popover-trigger="mouseenter" popover-animation="false" popover-append-to-body="true" ng-src="img/units/{{name}}.png" alt="{{localized_name}}" title="{{localized_name}}" />'
		};
	}])

	mod.directive('player', [function() {
		return {
			link: function(scope, element, attrs) {
				scope.position = scope.pos || 'right';
				scope.playerLink = (scope.slot.isUser == 'true') ? 'player/' + scope.slot.account_id : '';
				scope.pname = scope.slot.personaname;
				if (scope.slot.isUser == 'false') {
					element.children().css('color', 'inherit');
				}
			},
			scope: {
				slot: '=slot',
				pos: '@position'
			},
			template: '<a ng-href="{{playerLink}}">{{pname}}</a>'
		};
	}])

	mod.directive('mapCanvas', [function() {
		return {
			link: function(scope, element, attrs) {
				// load images
				var racks_img = {radiant: new Image(), dire: new Image()};
				racks_img.radiant.src = "img/map/racks_radiant.png";
				racks_img.dire.src = "img/map/racks_dire.png";

				var tower_img = {radiant: new Image(), dire: new Image()};
				tower_img.radiant.src = "img/map/tower_radiant.png";
				tower_img.dire.src = "img/map/tower_dire.png";

				var map = new Image();

				scope.$watch(scope.match)

				// because of the code order, the small images should load well before the large map
				$(map).load(function() {
					function prepareMap() {
						var originalSize = 1024;
						var scale = 0.625;
					
						// draw the image
						var canvas = $('#dota_map_canvas')
							.attr('width', (originalSize * scale) + 'px')
							.attr('height', (originalSize * scale) + 'px');
						var ctx = canvas[0].getContext('2d');

						ctx.scale(scale, scale);
						
						function toZeroPaddedBinary(val, length) {
							var binary = (parseInt(val)).toString(2);

							while (binary.length < length) {
								binary = "0" + binary;
							}

							return binary;
						}

						var towers = {};
						towers.radiant  = toZeroPaddedBinary(scope.match.tower_status_radiant, 11);
						towers.dire     = toZeroPaddedBinary(scope.match.tower_status_dire, 11);
						var racks = {};
						racks.radiant  = toZeroPaddedBinary(scope.match.barracks_status_radiant, 6);
						racks.dire     = toZeroPaddedBinary(scope.match.barracks_status_dire, 6);

						ctx.drawImage(map,0,0);

						// radiant
						if (towers.radiant[0] == "1") {
						   ctx.drawImage(tower_img.radiant, 131, 794); // ancient top
						}
						if (towers.radiant[1] == "1") {
						   ctx.drawImage(tower_img.radiant, 158, 817); // ancient bot
						}

						if (racks.radiant[0] == "1") {
							ctx.drawImage(racks_img.radiant, 233, 855); // bottom ranged
						}
						if (towers.radiant[2] == "1") {
							ctx.drawImage(tower_img.radiant, 252, 870); // t3 bot
						}
						if (racks.radiant[1] == "1") {
							ctx.drawImage(racks_img.radiant, 233, 878); // bottom melee
						}

						if (towers.radiant[5] == "1") {
							ctx.drawImage(tower_img.radiant, 200, 749); // t3 mid
						}
						if (racks.radiant[2] == "1") {
							ctx.drawImage(racks_img.radiant, 177, 754); // middle ranged
						}
						if (racks.radiant[3] == "1") {
							ctx.drawImage(racks_img.radiant, 193, 764); // middle melee
						}

						if (racks.radiant[4] == "1") {
							ctx.drawImage(racks_img.radiant, 65, 720); // top ranged
						}
						if (racks.radiant[5] == "1") {
							ctx.drawImage(racks_img.radiant, 95, 720); // top melee
						}
						if (towers.radiant[8] == "1") {
							ctx.drawImage(tower_img.radiant, 81, 700); // t3 top
						}

						if (towers.radiant[3] == "1") {
							ctx.drawImage(tower_img.radiant, 466, 880); // t2 bot
						}
						if (towers.radiant[4] == "1") {
							ctx.drawImage(tower_img.radiant, 803, 860); // t1 bot
						}
						if (towers.radiant[6] == "1") {
							ctx.drawImage(tower_img.radiant, 291, 662); // t2 mid
						}
						if (towers.radiant[7] == "1") {
						   ctx.drawImage(tower_img.radiant, 405, 576); // t1 mid
						}
						if (towers.radiant[9] == "1") {
							ctx.drawImage(tower_img.radiant, 105, 545); // t2 top
						}
						if (towers.radiant[10] == "1") {
							ctx.drawImage(tower_img.radiant, 105, 383); // t1 top
						}


						// dire
						if (towers.dire[0] == "1") {
							ctx.drawImage(tower_img.dire, 808, 180); // ancient top
						}
						if (towers.dire[1] == "1") {
							ctx.drawImage(tower_img.dire, 838, 202); // ancient bot
						}

						if (towers.dire[2] == "1") {
							ctx.drawImage(tower_img.dire, 891, 308); // t3 bot
						}
						if (racks.dire[0] == "1") {
							ctx.drawImage(racks_img.dire, 876, 288); // bottom ranged
						}
						if (racks.dire[1] == "1") {
							ctx.drawImage(racks_img.dire, 906, 288); // bottom melee
						}

						if (racks.dire[2] == "1") {
							ctx.drawImage(racks_img.dire, 763, 240); // middle ranged
						}
						if (racks.dire[3] == "1") {
							ctx.drawImage(racks_img.dire, 785, 253); // middle melee
						}
						if (towers.dire[5] == "1") {
							ctx.drawImage(tower_img.dire, 760, 260); // t3 mid
						}

						if (racks.dire[4] == "1") {
							ctx.drawImage(racks_img.dire, 740, 118); // top ranged
						}
						if (towers.dire[8] == "1") {
							ctx.drawImage(tower_img.dire, 720, 135); // t3 top
						}
						if (racks.dire[5] == "1") {
							ctx.drawImage(racks_img.dire, 740, 140); // top melee
						}


						if (towers.dire[3] == "1") {
							ctx.drawImage(tower_img.dire, 891, 474); // t2 bot
						}
						if (towers.dire[4] == "1") {
							ctx.drawImage(tower_img.dire, 887, 612); // t1 bot
						}
						if (towers.dire[6] == "1") {
							ctx.drawImage(tower_img.dire, 646, 362); // t2 mid
						}
						if (towers.dire[7] == "1") {
							ctx.drawImage(tower_img.dire, 549, 475); // t1 mid
						}
						if (towers.dire[9] == "1") {
							ctx.drawImage(tower_img.dire, 497, 119); // t2 top
						}
						if (towers.dire[10] == "1") {
							ctx.drawImage(tower_img.dire, 198, 120); // t1 top
						}
					}

					if (!scope.match) {
						// if no match data loaded yet, just wait for it
						var listener = scope.$watch('match', function() {
							prepareMap();
							listener();
						});
					} else {
						prepareMap();
					}
				});

				map.src = "img/map/dota_map.jpg";
			},
			scope: {
				match: '=match'
			},
			template: '<canvas id="dota_map_canvas" />'
		};
	}])

})();