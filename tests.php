<?php
	require_once("config.php");

	//instantiate
    $pdo = new pdoHelper();
    $pdo->debug = true;
        
    //example select:
    $data = $pdo->select("*", "users",  array(
                                            array("users.user_id", "=", 8)
                                        ),
                                        array(
                                            array("user_accounts", "user_accounts.user_id", "users.user_id"),
                                            array("accounts", "accounts.account_id", "user_accounts.account_id")
                                        )
                        );

    //example insert:
    $data = $pdo->insert(
    	array(
    		'users' => array('user_id' => NULL, 'username' => 'exampleUserName123', 'password' => saltHash('test'), 'email' => 'test@test.com', 'community_id' => 1, 'role_id' => 1)
    	)
    );
    
    //example update:
    $data = $pdo->update(
    	array(
    		'users' => array('username' => 'jamieLife', 'email' => 'jamielife@jamielife.com')
    	), 
    	array('user_id' => 5)
    );
    
    //example delete:
    $data = $pdo->delete(
    	'users', 
    	array(
    		array(
    			'user_id', '=', '9'
    		)
    	)
    );

	//example join:
    $data = $pdo->select(
    	"users.id, users.email, username, accounts.client_id, client_name, account_logo, accounts.account_name, accounts.account_id, account_type_id, role_id", 
    	"users",
		array(
			array("username", "=", 'exampleUserName123'),
			array("password", "=", saltHash('test')),
			array("enabled", "=", 1)
		),
		array(
			array("user_accounts", "user_accounts.user_id", "users.user_id"),
			array('accounts', 'accounts.account_id', 'user_accounts.account_id'),
			array('clients', 'accounts.client_id', 'clients.client_id')
		), false, true);
  