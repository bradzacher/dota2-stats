(function () {
	'use strict';

	var mod = angular.module('dotaStats', ['ngRoute', 'ngResource', 'ngAnimate', 'ui.bootstrap'])

	mod.config(['$routeProvider', '$locationProvider',
		function ($routeProvider, $locationProvider) {
			function promiseFn(service) {
				return service.promise;
			}
			var services = ['$game_modes', '$heroes', '$items', '$lobby_types', '$regions', '$users', '$abilities', '$units'];
			var promises = {};
			services.forEach(function(s) {
				promises[s] = [s, promiseFn];
			});
			
			$routeProvider
				.when('/', {
					controller: 'HomeController',
					templateUrl: 'templates/home.html'
				})

				////////////////////
				// Matches Routes //
				////////////////////
				.when('/matches', {
					controller: 'MatchesController',
					templateUrl: 'templates/matches.html',
					reloadOnSearch: false,
					resolve: {
						'heroes': promises['$heroes'],
						'game_modes': promises['$game_modes'],
						'lobby_types': promises['$lobby_types']
					}
				})
				.when('/matches/:page', {
					controller: 'MatchesController',
					templateUrl: 'templates/matches.html',
					reloadOnSearch: false,
					resolve: {
						'heroes': promises['$heroes'],
						'game_modes': promises['$game_modes'],
						'lobby_types': promises['$lobby_types']
					}
				})
				
				////////////////////////
				// Single Match Route //
				////////////////////////
				.when('/match/:match_id', {
					controller: 'MatchController',
					templateUrl: 'templates/match.html',
					resolve: {
						'heroes': promises['$heroes'],
						'items': promises['$items'],
						'abilities': promises['$abilities'],
						'units': promises['$units'],
						'game_modes': promises['$game_modes'],
						'lobby_types': promises['$lobby_types']
					}
				})

				////////////////////
				// Players Routes //
				////////////////////
				.when('/players', {
					controller: 'PlayersController',
					templateUrl: 'templates/players.html'
				})
				.when('/player/:account_id', {
					controller: 'PlayerController',
					templateUrl: 'templates/player.html',
					resolve: {
						'heroes': promises['$heroes'],
						'game_modes': promises['$game_modes'],
						'lobby_types': promises['$lobby_types']
					}
				})
				.when('/player/:account_id/:page_name/:page_num', {
					controller: 'PlayerController',
					templateUrl: 'templates/player.html',
					resolve: {
						'heroes': promises['$heroes'],
						'game_modes': promises['$game_modes'],
						'lobby_types': promises['$lobby_types']
					}
				})

				///////////////////
				// Heroes Routes //
				///////////////////
				.when('/heroes', {
					controller: 'HeroesController',
					templateUrl: 'templates/heroes.html'
				})
				.when('/hero/:hero_id', {
					controller: 'HeroController',
					templateUrl: 'templates/hero.html'
				})

				.when('/lineups', {
					controller: 'LineupsController',
					templateUrl: 'templates/lineups.html'
				})
				.when('/cumulative', {
					controller: 'CumulativeController',
					templateUrl: 'templates/cumulative.html'
				})
				.when('/backpacks', {
					controller: 'BackpacksController',
					templateUrl: 'templates/backpacks.html'
				})
				
				
				////////////////
				// 404 Routes //
				////////////////
				.when('/404', {
					controller: 'ErrorController',
					templateUrl: 'templates/404.html'
				})
				.when('/404/:url*', { // the star makes it a greedy slash consumer
					controller: 'ErrorController',
					templateUrl: 'templates/404.html'
				})
				.otherwise({
					redirectTo: function(pathObj, path, search) {
						if (path.substring(0,5) !== '/404/') {
							return '/404/' + path.substring(1);
						}
						return path;
					}
				})
			$locationProvider
					.html5Mode(true)
					.hashPrefix('!');
		}
	])

	// auto loader for the important data
	mod.controller('rootController', ['$scope', 'Page',
		function($scope, Page) {
			$scope.Page = Page;
		}
	])

	mod.factory('Page', ['$window', function($window) {
		var title = 'DotA 2 Stats and Tools';
		var loading = true;
		var error = false;
		var location = "";
		return {
			title: function() {
				return title;
			},
			setTitle: function(newTitle) {
				title = newTitle;
				error = false;
				location = $window.location.href;
			},
			loading: function() {
				return loading;
			},
			setLoading: function(newLoading) {
				loading = newLoading;
				location = $window.location.href;
			},
			setError: function(val) {
				error = true;
				location = $window.location.href;
			},
			error: function() {
				return error;
			},
			location: function() {
				return location;
			}
		};
	}])

	mod.factory('$exceptionHandler', ['$log', '$window', 'Page',
		function ($log, $window, Page) {
			return function (exception, cause) {
				$log.error(exception);
				$log.error(cause);
				
				$.ajax({
					type: 'POST',
					url: '/rest/report_error.php',
					contentType: 'application/x-www-form-urlencoded',
					data: {
						source: $window.location.href,
						error: exception.message,
						stack: exception.stack,
						cause: cause || ''
					}
				});
				
				Page.setError(true);
			};
		}
	]);
	
})();