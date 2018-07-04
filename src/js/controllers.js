(function () {
	'use strict';
	
	var mod = angular.module('dotaStats')
	
	// match controllers
	mod.controller('MatchesController', ['$scope', '$route', '$routeParams', '$location', '$timeout', '$matches', '$game_modes', '$lobby_types', 'Page',
		function($scope, $route, $routeParams, $location, $timeout, $matches, $game_modes, $lobby_types, Page) {
			Page.setTitle('Matches');

			// the data for the current page
			$scope.pageData = [];

			// items to display per page
			$scope.resultsPerPage = 25;

			// the maximum number of page numbers to show in pagination
			$scope.maxSize = 5;

			// the currently selected page
			$scope.currentPage = $routeParams.page || 1;

			// the total number of pages we have
			$scope.pagesTotal = $scope.currentPage;

			// total number of items in the set
			$scope.totalItems = $scope.pagesTotal * $scope.resultsPerPage;
			
			// loads a specific page from the server
			$scope.loadPage = function(page) {
				Page.setLoading(true);

                var doingCacheBuster = false;
                var getMatches = function (page, allowCache) {
                    var params = {page: page};
                    if (allowCache === false) {
                        // append a unique param to the url to force all caches to fetch a new copy
                        params._ = new Date().getTime();
                        doingCacheBuster = true;
                    }
                    
                    $matches.get(params, processResponse(page));
                }
                
                var processResponse = function(pageNum) {
                    return function(data, headers) {
                        // workaround for stubborn proxies that refuse to get new data.
                        var calculatedAge = new Date() - new Date(headers('Last-Modified'));
                        if (calculatedAge > 86400000 && !doingCacheBuster) { // if data older than 24 hours, force refresh unless we have already tried to bust the cache
                            getMatches(pageNum, false);
                            return;
                        }
                        
                        Page.setLoading(false);

                        $scope.pageData = data.result.matches;

                        $scope.pageData.forEach(function(el) {
                            // add a string-based win field
                            if (el.radiant_win == 1) {
                                el.winner = 'Radiant';
                                el.winnerClass = 'radiant';
                            } else {
                                el.winner = 'Dire';
                                el.winnerClass = 'dire';
                            }

                            el.players.forEach(function(p) {
                                if (p.isUser === 'true') {
                                    p.isUser = true;
                                } else {
                                    p.isUser = false;
                                }
                            });
                            
                            // replace the game_mode id with its actual name
                            el.game_mode = $game_modes.get(el.game_mode).name;
                            if ($lobby_types.get(el.lobby_type).name == 'Ranked') {
                                el.ranked = true;
                                if (el.game_mode === 'Captain\'s Mode') {
                                    el.game_mode = 'CM';
                                }
                            }
                        });

                        $scope.pagesTotal = data.result.total_pages;
                        //$scope.resultsPerPage = data.result.num_results;
                        $scope.totalItems = $scope.resultsPerPage * $scope.pagesTotal;

                        setCurrentPage(page);
                        
                        // less than IE11 ? no pointer-events functionality, so javascript to fix it
                        if ($(document).hasClass('lt-ie11')) {
                            // queue for after the document tree has updated
                            $timeout(function() {
                                $('.known-user').mouseenter(function(e) {
                                    console.log(e);
                                    $(this).next().children().mouseenter();
                                })
                                .mouseleave(function(e) {
                                    console.log(e);
                                    $(this).next().children().mouseleave();
                                });
                            });
                        }
                    };
                };
                
				getMatches(page-1);
			}

			$scope.loadPage($scope.currentPage);

			function setCurrentPage (page) {
				$scope.currentPage = page;
				$location.path('matches/' + $scope.currentPage);
			}

			// short circuit the location changer so it doesn't cause reloads when we navigate pages
			var lastRoute = $route.current;
			$scope.$on('$locationChangeSuccess', function(event) {
				// if we're just changing page then make sure no reload occurs
				if ($route.current.originalPath == "/matches/:page") {
					$route.current = lastRoute;

				// if they clicked the header link for matches, then return to page 1
				} else if ($route.current.originalPath == "/matches") {
					$scope.loadPage(1);
				}
			});
		}
	])
	mod.controller('MatchController', ['$scope', '$filter', '$routeParams', '$match', '$abilities', 'Page',
		function($scope, $filter, $routeParams, $match, $abilities, Page) {
			Page.setLoading(true);
			
			$scope.iter = [];
			for (var i = 1; i <= 25; i++) {
				$scope.iter.push(i);
			}
		
			$scope.match_id = $routeParams.match_id;
			$scope.matchdata = null;

			Page.setTitle('Match #' + $scope.match_id);

			$match.get({match_id: $scope.match_id}, function(data) {
				if (data.result.status === 1) {
					Page.setLoading(false);

					$scope.matchdata = data.result.matches[0];

					$scope.matchdata.players.forEach(function(p) {
						// try to load up the hero's abilities, but if we fail just ignore the player
						try {
							var stats = $abilities.get(5002);
							var abilities = $abilities.getHeroAbilities(p.hero_id);
							var idToSlot = {5002: 4};
							var abilitiesIdToObj = {5002:stats};
							
							var abilitiesObj = [{},{},{},{},{obj:stats}]
							
							abilities.forEach(function(a) {
								abilitiesObj[a.slot] = abilitiesObj[a.slot] || {};
								abilitiesObj[a.slot].obj = a;
								idToSlot[a.id] = a.slot;
								abilitiesIdToObj[a.id] = a;
							});
							
							p.abilities_upgrades.forEach(function(a) {
								var ability = abilitiesObj[idToSlot[a.ability]];
								if (!ability) {
									ability = abilitiesObj[4];
									ability.obj = stats;
								} else {
									ability.obj = abilitiesIdToObj[a.ability];
								}
								ability[a.level] = true;
							});
							
							p.abilities = abilitiesObj;
						} catch (e) {
							throw e;
						}
					});
					
					$scope.teams = [];
					$scope.teams[0] = {
						players: $filter('radiant')($scope.matchdata.players),
						name: $scope.matchdata.radiant_name || 'Radiant',
						style: 'radiant'
					}
					$scope.teams[1] = {
						players: $filter('dire')($scope.matchdata.players),
						name: $scope.matchdata.dire_name || 'Dire',
						style: 'dire'
					}
					
					$scope.hasPickBans = $scope.matchdata.pickbans.radiant.length > 0 && $scope.matchdata.pickbans.dire.length > 0;
				}
			});
		}
	])

	// player controllers
	mod.controller('PlayersController', ['$scope', 'Page',
		function($scope, Page) {
			Page.setTitle('Players');
			Page.setLoading(false);
		}
	])
	mod.controller('PlayerController', ['$scope', '$routeParams', 'Page', '$player', '$game_modes', '$lobby_types',
		function($scope, $routeParams, Page, $player, $game_modes, $lobby_types) {
			Page.setLoading(true);
			
			$scope.account_id = $routeParams.account_id;
			Page.setTitle('Player #' + $scope.account_id);
			
			$scope.invalid = true;
			
			Page.setLoading(false);
			$scope.invalid = false;
		}
	])
	mod.controller('PlayerSummaryController', ['$scope', '$route', '$routeParams', '$location', 'Page', '$player', '$game_modes', '$lobby_types',
		function($scope, $route, $routeParams, $location, Page, $player, $game_modes, $lobby_types) {
			$scope.account_id = $routeParams.account_id;
			$scope.active = $routeParams.page_name == undefined || $routeParams.page_name == 'summary';
		
			$scope.selectTab = function() {
				Page.setLoading(true);
				
				$scope.invalid = true;
					
				$player.get({account_id: $scope.account_id}, function(data) {
					if (data.result.status === 1) {
						$scope.invalid = false;
					
						$scope.profile = data.result.player.profile;
						$scope.heroes = data.result.player.heroes;
						$scope.modes = data.result.player.modes;
						$scope.buddies = data.result.player.buddies;
						$scope.matches = data.result.player.matches;
						$scope.totals = data.result.player.totals;
						
						var calculateWidth = function (val, max) {
							return ((val / max) * 100) + '%';
						}
						var winsToRate = function(obj) {
							obj.win_rate = (Math.floor((obj.wins / obj.matches_played) * 100 * 10) / 10) + '%';
							
							if (obj.widths === undefined) {
								obj.widths = {};
							}
							obj.widths['win_rate'] = obj.win_rate;
						}
						var getMax = function(arr, keys) {
							var max = {};
							
							// find the max val for each key
							keys.forEach(function(k) {
								var maxVal = -Number.MAX_VALUE;
								arr.forEach(function(e) {
									maxVal = Math.max(maxVal, e[k]);
								});
								max[k] = maxVal;
							});
							
							arr.forEach(function(e) {
								e.widths = {};
							});
							
							// calculate the %age width for each element
							keys.forEach(function(k) {
								arr.forEach(function(e) {
									e.widths[k] = calculateWidth(e[k], max[k]);
								});
							});
						}
						
						getMax($scope.heroes, ['kda', 'matches_played']);
						$scope.heroes.forEach(function(e) {
							winsToRate(e);
							
							// round KDA
							e.kda = Math.floor(e.kda * 100) / 100;
						});
						
						
						getMax($scope.modes, ['matches_played']);
						$scope.modes.forEach(function(e) {
							winsToRate(e);
							
							// replace the game_mode id with its actual name
							e.game_mode = $game_modes.get(e.game_mode).name;
							if ($lobby_types.get(e.lobby_type).name == "Ranked") {
								e.ranked = true;
							}
						});
						
						getMax($scope.buddies, ['matches_played']);
						$scope.buddies.forEach(function(e) {
							winsToRate(e);
						});
						
						$scope.matches.forEach(function(e) {
							if (e.win == 1) {
								e.win = true;
							} else {
								e.win = false;
							}
						});
						
						winsToRate($scope.totals);
						var max = Math.max($scope.totals.radiant_matches, $scope.totals.dire_matches);
						$scope.totals.widths.radiant = calculateWidth($scope.totals.radiant_matches, max);
						$scope.totals.widths.dire = calculateWidth($scope.totals.dire_matches, max);
						$scope.totals.kda = Math.floor($scope.totals.kda * 100) / 100;
						
						Page.setTitle($scope.profile.personaname + ' (Player #' + $scope.account_id + ')');
					} else {
						$scope.invalid = true;
						$scope.invalidReason = data.result.message;
					}
					
					Page.setLoading(false);
				});
				
				$location.path('player/' + $scope.account_id);
				var lastRoute = $route.current;
				$scope.$on('$locationChangeSuccess', function(event) {
					// if we're just changing page then make sure no reload occurs
					if ($route.current.originalPath == "/player/:account_id" &&  $route.current.params.account_id == $scope.account_id) {
						$route.current = lastRoute;
					}
				});
			}
		}
	])
	mod.controller('PlayerMatchesController', ['$scope', '$route', '$routeParams', '$location', 'Page', '$player_matches', '$game_modes', '$lobby_types',
		function($scope, $route, $routeParams, $location, Page, $player_matches, $game_modes, $lobby_types) {
			$scope.account_id = $routeParams.account_id;
				
			$scope.active = $routeParams.page_name == 'matches';
			$scope.selectTab = function() {
				// the data for the current page
				$scope.pageData = [];

				// items to display per page
				$scope.resultsPerPage = 25;

				// the maximum number of page numbers to show in pagination
				$scope.maxSize = 5;

				// the currently selected page
				$scope.currentPage = $routeParams.page_num || 1;

				// the total number of pages we have
				$scope.pagesTotal = $scope.currentPage;

				// total number of items in the set
				$scope.totalItems = $scope.pagesTotal * $scope.resultsPerPage;
				
				// loads a specific page from the server
				$scope.loadPage = function(page) {
					Page.setLoading(true);

					var getMatches = function (page, allowCache) {
						var params = {page: page, account_id: $scope.account_id};
						if (allowCache === false) {
							// append a unique param to the url to force all caches to fetch a new copy
							params._ = new Date().getTime();
						}
						
						$player_matches.get(params, processResponse(page));
					}
					
					var processResponse = function(pageNum) {
						return function(data, headers) {
							// workaround for stubborn proxies that refuse to get new data.
							var calculatedAge = new Date() - new Date(headers('Last-Modified'));
							if (calculatedAge > 86400000) { // if data older than 24 hours, force refresh
								getMatches(pageNum, false);
								return;
							}
							
							Page.setLoading(false);

							$scope.pageData = data.result.matches;
							console.log($scope.pageData);
							$scope.pageData.forEach(function(el) {
								// add a string-based win field
								if (el.radiant_win == 1 && el.player_data.player_slot >> 7 == 0 ||
									el.radiant_win == 0 && el.player_data.player_slot >> 7 == 1) {
									el.win = true;
								} else {
									el.win = false;
								}
								
								// replace the game_mode id with its actual name
								el.game_mode = $game_modes.get(el.game_mode).name;
								if ($lobby_types.get(el.lobby_type).name == "Ranked") {
									el.ranked = true;
								}
							});

							$scope.pagesTotal = data.result.total_pages;
							//$scope.resultsPerPage = data.result.num_results;
							$scope.totalItems = $scope.resultsPerPage * $scope.pagesTotal;

							setCurrentPage(page);
							
							// less than IE11 ? no pointer-events functionality, so javascript to fix it
							if ($(document).hasClass('lt-ie11')) {
								// queue for after the document tree has updated
								$timeout(function() {
									$('.known-user').mouseenter(function(e) {
										console.log(e);
										$(this).next().children().mouseenter();
									})
									.mouseleave(function(e) {
										console.log(e);
										$(this).next().children().mouseleave();
									});
								});
							}
						};
					};
					
					getMatches(page-1);
				}

				$scope.loadPage($scope.currentPage);

				function setCurrentPage (page) {
					$scope.currentPage = page;
					$location.path('player/' + $scope.account_id + '/matches/' + $scope.currentPage);
				}

				// short circuit the location changer so it doesn't cause reloads when we navigate pages
				var lastRoute = $route.current;
				$scope.$on('$locationChangeSuccess', function(event) {
					// if we're just changing page then make sure no reload occurs
					if ($route.current.originalPath == "/player/:account_id/:page_name/:page_num") {
						$route.current = lastRoute;

					// if they clicked the header link for matches, then return to page 1
					} else if ($route.current.originalPath == "/player/:account_id/:page_name") {
						$scope.loadPage(1);
					}
				});
			}
		}
	])

	// hero controllers
	mod.controller('HeroesController', ['$scope', 'Page',
		function($scope, Page) {
			Page.setTitle('Heroes');
			Page.setLoading(false);
		}
	])
	mod.controller('HeroController', ['$scope', '$routeParams', 'Page',
		function($scope, $routeParams, Page) {
			$scope.hero_id = $routeParams.hero_id;
			Page.setTitle('Hero #' + $scope.hero_id);
			Page.setLoading(false);
		}
	])


	// other controllers
	mod.controller('HomeController', ['$scope', 'Page',
		function($scope, Page) {
			Page.setTitle('DotA 2 Stats and Tools');
			Page.setLoading(false);
		}
	])
	mod.controller('LineupsController', ['$scope', 'Page',
		function($scope, Page) {
			Page.setTitle('Lineups');
			Page.setLoading(false);
		}
	])
	mod.controller('CumulativeController', ['$scope', 'Page',
		function($scope, Page) {
			Page.setTitle('Cumulative Stats');
			Page.setLoading(false);
		}
	])
	mod.controller('BackpacksController', ['$scope', 'Page',
		function($scope, Page) {
			Page.setTitle('Backpacks');
			Page.setLoading(false);
		}
	])
	mod.controller('ErrorController', ['$scope', '$routeParams', 'Page',
		function($scope, $routeParams, Page) {
			Page.setTitle('Page Not Found');
			Page.setLoading(false);
			
			$scope.url = $routeParams.url;
		}
	])

})();
                            