(function () {
	'use strict';
	
	function convertMS(ms) {
		var x = Math.floor(ms / 1000) || 0;
		return convertS(x);
	}
	function convertS(s) {
		s = s || 0;

		var seconds = s % 60;
		s = Math.floor(s / 60) || 0;
		var minutes = s % 60;
		s = Math.floor(s / 60) || 0;
		var hours = s % 24;
		s = Math.floor(s / 24) || 0;
		var days = s;

		return {
			seconds: seconds,
			minutes: minutes,
			hours: hours,
			days: days
		};
	}

	var mod = angular.module('dotaStats')
	/**********************
	 * filter definitions *
	 **********************/

	// team filters
	mod.filter('radiant', function() {
		return function(teams) {
			var ret = [];
			if (teams) {
				teams.forEach(function (slot) {
					if (slot.player_slot >> 7 === 0) {
						ret.push(slot);
					}
				});
			}
			return ret;
		}
	})
	mod.filter('dire', function() {
		return function(teams) {
			var ret = [];
			if (teams) {
				teams.forEach(function (slot) {
					if (slot.player_slot >> 7 === 1) {
						ret.push(slot);
					}
				});
			}

			if (ret.length === 0) {
				return null;
			}
			return ret;
		}
	})
	mod.filter('timeSince', function() {
		return function(time) {
			var ms = new Date() - new Date(time);

			var x = convertMS(ms);

			var str = '';

			if (x.days != 0) {
				str = x.days + ' days';
			} else if (x.hours != 0) {
				str = x.hours + ' hours';
			} else if (x.minutes != 0) {
				str = x.minutes + ' minutes';
			} else {
				str = x.seconds + ' seconds';
			}
			return str;
		};
	})
	mod.filter('duration', function() {
		return function(time) {
			var x = convertS(time);

			var str = '';
			if (x.days != 0) {
				str += x.days + 'd';
			}
			if (x.hours != 0 || x.days != 0) {
				str += x.hours + 'h';
			}
			str += x.minutes + 'm';
			str += x.seconds + 's';

			return str;
		};
	})
	mod.filter('game_mode', ['$game_modes', function($game_modes) {
		return function(id) {
			if (id === undefined) {
				return '';
			}
			return $game_modes.get(id).name;
		}
	}])
	mod.filter('lobby_type', ['$lobby_types', function($lobby_types) {
		return function(id) {
			if (id === undefined) {
				return '';
			}
			return $lobby_types.get(id).name;
		}
	}])
	mod.filter('ucfirst', [
		function() {
			return function(str) {
				if (str !== undefined && str !== null) {
					if (str.length > 0) {
						return str.charAt(0).toUpperCase() + str.substr(1);
					}
				}
				return '';
			};
		}
	]);
})();