<?php
/**
 * All Info About One Player
 */
class player_mapper_db {
	public function __construct() {
	}
	
	public function set_steamid($id) {
		$this->_steam_id = (string)$id;
	}
	
	public function get_steamid() {
		return $this->_steam_id;
	}
	
	/**
	 * @param steam_id or null
	 * @return player object
	 */
	public function load($id = null, $isUser = false) {
		if(!is_null($id)) {
			$this->_steam_id = (string)$id;
		}
		$player = new player();
		
		if(empty($this->_steam_id)) {
			return $player;
		}
		
		$db = db::obtain();
        
        $table = $isUser ? 'users' : 'players';
        
		$result = $db->query_first_pdo('SELECT * FROM ' . db::real_tablename($table) . ' WHERE steamid = ?', array($this->get_steamid()));
		$player->set_array($result);
		return $player;
	}
	
	/**
	 * Determines whether the player should be inserted or updated in the db
	 * @param player object
	 */
	public function save(player $player, $isUser = false) {
		if(player_mapper_db::player_exists($player->get('steamid'), $isUser)) {
			$this->update($player, $isUser);
		}
		else {
			$this->insert($player, $isUser);
		}
	}
	
	private function insert(player $player, $isUser) {
		$db = db::obtain();

  		$data = array('account_id' => player::convert_id($player->get('steamid')));
        
        if ($isUser) {
    		$data = array_merge($data, $player->get_data_array());
            
            $table = 'users';
        } else {
            $data['personaname'] = $player->get('personaname');
            $data['steamid'] = $player->get('steamid');
            
            $table = 'players';
        }
        
		$db->insert_pdo(db::real_tablename($table), $data);
	}
	
	private function update(player $player, $isUser) {
		$db = db::obtain();

  		$data = array('account_id' => player::convert_id($player->get('steamid')));
        
        if ($isUser) {
    		$data = array_merge($data, $player->get_data_array());
            
            $table = 'users';
        } else {
            $data['personaname'] = $player->get('personaname');
            $data['steamid'] = $player->get('steamid');
            
            $table = 'players';
        }
        
        
		$db->update_pdo(db::real_tablename($table), $data, array('steamid' => $player->get('steamid')));
	}
	
	/**
	 * @param string steam_id
	 * @return bool
	 */
	public static function player_exists($id = null, $isUser) {
		if(is_null($id)) {
			return;
		}
		
        $table = $isUser ? 'users' : 'players'; 
        
		$db = db::obtain();
		$result = $db->query_first_pdo('SELECT * FROM ' . db::real_tablename($table) . ' WHERE steamid = ?', array($id));
		if($result['steamid'] == (string)$id) {
			return true;
		}
		return false;
	}
}
?>
