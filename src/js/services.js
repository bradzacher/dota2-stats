(function () {
	'use strict';
	
	var mod = angular.module('dotaStats')
	/***********************
	 * service definitions *
	 ***********************/

	mod.service('$matches', ['$resource', function($resource) {
		return $resource('rest/matches.php');
	}])

	mod.service('$match', ['$resource', function($resource) {
		return $resource('rest/match.php');
	}])

	mod.service('$player_matches', ['$resource', function($resource) {
		return $resource('rest/player_matches.php');
	}])
	
	mod.service('$game_modes', ['$http', function($http) {
		return get($http, 'game_modes');
	}])

	mod.service('$heroes', ['$http', function($http) {
		return get($http, 'heroes');
	}])

	mod.service('$items', ['$http', function($http) {
		return get($http, 'items');
	}])

	mod.service('$lobby_types', ['$http', function($http) {
		return get($http, 'lobby_types');
	}])

	mod.service('$regions', ['$http', function($http) {
		return get($http, 'regions');
	}])

	mod.service('$users', ['$http', function($http) {
		return get($http, 'users');
	}])

	mod.service('$player', ['$resource', function($resource) {
		return $resource('rest/player.php');
	}])

	mod.service('$units', ['$http', function($http) {
		var obj = get($http, 'units');
		
		obj.get = function(name) {
			var arr = this.getAll();
			
			var ret = undefined;
			arr.forEach(function(e) {
				if (e.name == name) {
					ret = e;
				}
			});
			
			return ret;
		}
		
		return obj;
	}])

	mod.service('$abilities', ['$http', function($http) {
		var obj = get($http, 'abilities');
		obj.getHeroAbilities = function (id) {
			var data = this.getAll();
			
			var arr = [];
			data.forEach(function(a) {
				if (a.hero_id == id) {
					arr.push(a);
				}
			});
			
			return arr;
		};
		return obj;
	}])

	function get($http, name) {
		var url = 'rest/' + name + '.php';

		var responseData = null;
		
		// by returning the promise, we can ask the controllers to not load until the call resolves
		var promise = $http.get(url, {cache: true}).success(function(data) {
			responseData = reindexById(data.result[name]);
		});
		return {
			promise: promise,
			get: function(index) {
				if (responseData != null) {
					return responseData[index];
				}
				return null;
			},
			getAll: function() {
				return responseData;
			}
		}
	}

	function reindexById(arr) {
		var newArr = [];
		arr.forEach(function(el) {
			newArr[el.id] = el;
		})
		return newArr;
	}

})();