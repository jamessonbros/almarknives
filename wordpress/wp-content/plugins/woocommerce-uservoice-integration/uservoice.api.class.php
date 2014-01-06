<?php

require_once('oauth/oauthsimple.class.php');

if ( ! class_exists( 'OAuthSimple' ) || class_exists( 'UserVoice_API' ) ) return;

global $uv_oauth;
$uv_oauth = new OAuthSimple();

class UserVoice_API {

	var $uv_oauth;
	var $api_key;
	var $api_sec;
	var $req_url;
	var $acc_url;
	var $accname;
	var $api_url;
	var $callback;
	var $forum_url;
	var $reply_msg;
	var $oauth_signatures = array();
	var $tokens = array();

	public function __construct() {

		global $uv_settings;

		$page_url = false;
		if( isset( $_GET['callback'] ) && strlen( $_GET['callback'] ) > 0 ) {
			$page_url = $_GET['callback'];
		} else {
			if( isset( $post ) ) {
				$page_url = get_permalink( $post->ID );
			}
		}

		$this->api_key = ( isset( $uv_settings['uv_api_key'] ) ) ? trim( $uv_settings['uv_api_key'] ) : false;
		$this->api_sec = ( isset( $uv_settings['uv_api_secret'] ) ) ? trim( $uv_settings['uv_api_secret'] ) : false;
		$this->req_url = ( isset( $uv_settings['uv_api_request_url'] ) ) ? trim( $uv_settings['uv_api_request_url'] ) : false;
		$this->acc_url = ( isset( $uv_settings['uv_api_access_url'] ) ) ? trim( $uv_settings['uv_api_access_url'] ) : false;
		$this->accname = ( isset( $uv_settings['uv_api_accname'] ) ) ? trim( $uv_settings['uv_api_accname'] ) : false;
		$this->api_url = ( $this->accname ) ? 'https://' . trim( $this->accname ) . '.uservoice.com/api/v1' : false;
		$this->callback = ( $page_url ) ? $page_url : false;
		$this->forum_url = ( isset( $uv_settings['uv_api_forum_link'] ) ) ? trim( $uv_settings['uv_api_forum_link'] ) : false;
		$this->reply_msg = ( isset( $uv_settings['uv_api_reply_msg'] ) ) ? trim( $uv_settings['uv_api_reply_msg'] ) : false;

		$this->uv_oauth = new OAuthSimple( $this->api_key , $this->api_sec );

	}

	/**
	 * Get OAuth signatures from WC settings or cookie
	 * @return array Signatures
	 */
	private function get_signatures() {

		if( !isset( $_COOKIE['uservoice_api_signatures'] ) ) {
			if( $this->api_key && $this->api_sec ) {
	    		$this->oauth_signatures = array( 'consumer_key' => $this->api_key , 'shared_secret' => $this->api_sec );
	    	}
	    } else {
	        $json_signatures = stripslashes( $_COOKIE['uservoice_api_signatures'] );
	        if( $json_signatures ) {
	        	$this->oauth_signatures = json_decode( $json_signatures , true );
	        }
	    }

	}

	/**
	 * Get request and access tokens for OAuth requests
	 * @param  boolean $new_auth Indicate whether this is a new authorisation or not
	 * @return void
	 */
	public function auth( $new_auth = true ) {

		// Redirect user to My Account page if not logged in
		if( !is_user_logged_in() ) {
			$my_account = get_permalink( woocommerce_get_page_id( 'myaccount' ) );
			header( 'Location:' . $my_account );
			exit();
		}

		// Get OAuth signatures from site API details or cookie
		$this->get_signatures();

		if( $new_auth ) {

			// Get request token for OAuth requests
			$this->get_request_token();

			// Exchange request token for OAuth access token
	        $this->exchange_request_token();

	    }
	}

	/**
	 * Get request token from UserVoice
	 * @return void
	 */
	private function get_request_token() {

		$this->uv_oauth->reset();

		$result = $this->uv_oauth->sign( array(
            'path' => $this->req_url,
            'parameters'=> array(
            	'oauth_callback'=> $this->callback
        	),
            'signatures'=> $this->oauth_signatures)
		);

        $r = $this->process_result( $result );

        parse_str($r, $returned_items);
        $oauth_token = $returned_items['oauth_token'];
        $oauth_secret = $returned_items['oauth_token_secret'];

        $this->tokens = array(
        	'oauth_token' => $oauth_token,
        	'oauth_secret' => $oauth_secret
    	);
	}

	/**
	 * Exchange request token for access token
	 * @return void
	 */
	private function exchange_request_token() {

		$this->uv_oauth->reset();

		$this->oauth_signatures['oauth_token'] = $this->tokens['oauth_token'];
		$this->oauth_signatures['oauth_secret'] = $this->tokens['oauth_secret'];

        $result = $this->uv_oauth->sign( array(
            'path'      => $this->acc_url,
            'parameters'=> array(
                'oauth_token' => $this->tokens['oauth_token']
            ),
            'signatures'=> $this->oauth_signatures )
        );

        $r = $this->process_result( $result );

        parse_str($r, $returned_items);
        if( is_array( $returned_items ) && count( $returned_items ) > 0 ) {
	        $access_token = $returned_items['oauth_token'];
	        $access_token_secret = $returned_items['oauth_token_secret'];
	    } else {
	    	$access_token = null;
	    	$access_token_secret = null;
	    }

        $this->oauth_signatures['oauth_token'] = $access_token;
        $this->oauth_signatures['oauth_secret'] = $access_token_secret;

        // Create signatures cookie
        $json_signatures = json_encode( $this->oauth_signatures );
        setcookie( "uservoice_api_signatures" , $json_signatures );

        // Reload page once cookie is set
        header( 'Location:' . $this->callback );
        exit;
	}

	/**
	 * Get single ticket details
	 * @param  numeric $ticketid ID of ticket to retrieve
	 * @return array Ticket details
	 */
	private function get_ticket( $ticketid = false ) {

		if( $ticketid ) {

			$this->uv_oauth->reset();

	        $result = $this->uv_oauth->sign(array(
	            'path' => $this->api_url . '/tickets/' . $ticketid . '.json',
	            'signatures'=> $this->oauth_signatures)
	        );

	        $r = $this->process_result( $result );

	        return $r;
		}

		return false;
	}

	/**
	 * Retrieve paginated list of tickets for currently logged in user
	 * @return array Ticket list
	 */
	private function get_ticket_list() {
		global $current_user;

		wp_get_current_user();

		$this->uv_oauth->reset();

		$uv_page = ( isset( $_GET['uv_page'] ) ) ? $_GET['uv_page'] : 1;
        $uv_per_page = 10;
        $user_email = ( $current_user->user_email ) ? $current_user->user_email : false;

        if( $user_email && strlen( $user_email ) > 0 ) {

            $result = $this->uv_oauth->sign(array(
                'path' => $this->api_url . '/tickets/search.json',
                'parameters'=> array(
                    'page' => $uv_page,
                    'per_page' => $uv_per_page,
                    'query' => 'from:' . $user_email . ' status:open,closed'
                ),
                'signatures'=> $this->oauth_signatures)
            );

            $r = $this->process_result( $result );

            return $r;
        }

		return false;
	}

	/**
	 * Send API request
	 * @param  array $result Returned array from OAuth signing
	 * @param  boolean $debug  Turn debug on or off
	 * @return array Relevant OAuth tokens
	 */
	private function process_result( $result = false , $debug = false ) {

		if( $result ) {

			$ch = curl_init();
		    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		    curl_setopt($ch, CURLOPT_URL, $result['signed_url']);
		    $r = curl_exec($ch);
		    curl_close($ch);

		    if( $debug ) {
		    	echo '<pre>'; print_r( $r ); echo '</pre>'; die();
		    }

		    return $r;
		}

		return false;
	}

	/**
	 * Display ticket overview page or single ticket
	 * @return void
	 */
	public function ticket_overview_page() {
		global $current_user , $post;

		wp_get_current_user();

		if( !isset( $_COOKIE['uservoice_api_signatures'] ) ) {

			// Show link to My Account page if user is not logged in
			if( !is_user_logged_in() ) {
				$my_account = get_permalink( woocommerce_get_page_id( 'myaccount' ) );
				$msg = '<a href="' . $my_account . '">Click here to login.</a>';
			} else {
				$link = add_query_arg( array( 'uv_auth' => 'true' , 'callback' => home_url( $_SERVER['REQUEST_URI'] ) ) );
				$msg = '<a href="' . $link . '">Click here to authorise ' . get_bloginfo( 'name' ) . ' to retrieve your tickets from UserVoice.</a>';
			}

			return $msg;

		} else {

			$page_url = get_permalink( $post->ID );

			$this->auth( false );

			// Get ticket list or single ticket data
		    if( isset( $_GET['ticket'] ) ) {
		        $display = 'show_ticket';
		        $result = $this->get_ticket( $_GET['ticket'] );
		    } else {
		        $display = 'list_tickets';
		        $result = $this->get_ticket_list();
		    }

		    $response = json_decode($result);

		    $data = false;
		    if( isset( $response->tickets ) ) {
		        $data = $response->tickets;
		    } else if( isset( $response->ticket ) ) {
		        $data = $response->ticket;
		    }

		    // Setup pagination
		    if( isset( $response->response_data ) ) {
			    $total_tickets = $response->response_data->total_records;
			    if( $total_tickets && $total_tickets > 0 ) {

			        $ticket_nav = '<div class="uv_ticket_nav">';

			        if( $response->response_data->page > 1 ) {
			            $prev_page = $uv_page + 1;
			            $ticket_nav .= '<a href="' . $page_url . '?uv_page=' . $prev_page . '">&laquo; Previous</a>';
			        }

			        if( $total_tickets > $response->response_data->page * $response->response_data->per_page ) {
			            $next_page = $response->response_data->page + 1;
			            $ticket_nav .= '<a href="' . $page_url . '?uv_page=' . $next_page . '">Next &raquo;</a>';
			        }

			        $ticket_nav .= '</div>';
			    } else {
			        $total_tickets = 0;
			    }
			}

		    switch( $display ) {
                case 'list_tickets': default:

                    if( isset( $data ) && $total_tickets > 0 ) {

                        $ticketlist = '<div class="total-tickets">' . $total_tickets . ' Total</div>';

                        foreach( $data as $k => $ticket ) {
                            $date_created = date( 'l, j F Y G:i ' , strtotime( $ticket->created_at ) ) . ' GMT';
                            $ticket_link = add_query_arg( 'ticket', $ticket->id );
                            $ticketlist .= '<div class="ticket">
                                                <div class="subject"><a href="' . $ticket_link . '">' . $ticket->subject . '</a></div>
                                                <ul class="ticket_meta">
                                                    <li><span class="label">Ticket ID:</span> #' . $ticket->ticket_number . '</li>
                                                    <li><span class="label">Created:</span> ' . $date_created . '</li>
                                                    <li><span class="label">Status:</span> ' . ucfirst( $ticket->state ) . '</li>
                                                </ul>
                                            </div>';
                        }

                        $ticketlist .= $ticket_nav;

                        echo $ticketlist;

                    } else {
                        echo '<div class="ticket_response">
                                <p>You have not yet logged any support tickets with us.<br/>Find our knowledge base and support forum here: <a href="' . $this->forum_url . '">' . $this->forum_url . '</a>.</p>
                              </div>';
                    }

                break;

                case 'show_ticket':

                    if( isset( $data ) ) {

                        $date_created = date( 'l, j F Y H:i ' , strtotime( $data->created_at ) ) . ' GMT';

                        $ticket_info = '<h5>' . $data->subject . '</h5>
                                        <ul class="ticket_meta">
                                            <li><span class="label">Ticket ID:</span> #' . $data->ticket_number . '</li>
                                            <li><span class="label">Created:</span> ' . $date_created . '</li>
                                            <li><span class="label">Status:</span> ' . ucfirst( $data->state ) . '</li>';

                        foreach( $data->custom_fields as $field ) {
                            $ticket_info .= '<li><span class="label">' . $field->key . ':</span> ' . $field->value . '</li>';
                        }
                        $ticket_info .= '</ul>';

                        // Convert messages object to array and reverse sort order
                        $messages_json = json_encode( $data->messages );
                        $messages = json_decode( $messages_json , true );
                        krsort( $messages );

                        $c = 0;
                        $auth = true;
                        foreach( $messages as $msg ) {

                        	// DO not show ticket if logged in user did not create it
                        	if( $c == 0 ) {
                        		$user_email = $current_user->user_email;
                        		if( $msg['sender']['email'] != $user_email ) {
                        			$auth = false;
                        			break;
                        		}
                        		++$c;
                        	}

                            $date_created = date( 'l, j F Y H:i ' , strtotime( $msg['created_at'] ) ) . ' GMT';
                            $class = ( $msg['is_admin_response'] == 1 ) ? ' admin_response' : '';
                            $ticket_info .= '<div class="ticket_message' . $class . '">
                                                <div class="message_meta">
                                                    <div class="sender_avatar"><a href="' . $msg['sender']['url'] . '"><img src="' . $msg['sender']['avatar_url'] . '" width="40" height="40" border="0" /></a></div>
                                                    <div class="sender_name"><a href="' . $msg['sender']['url'] . '">' . $msg['sender']['name'] . '</a></div>
                                                    <div class="sender_title">' . $msg['sender']['title'] . '</div>
                                                    <div class="date_created">' . $date_created . '</div>
                                                 </div>';
                            $ticket_info .= '<div class="body">' . $this->format_links_in_text( nl2br( $msg['body'] ) ) . '</div>';

                            // Display message attachments
                            if( count( $msg['attachments'] ) > 0 ) {
                                $ticket_info .= '<div class="attachments">
                                                    <div class="title">Attachments:</div>
                                                    <ul>';

                                foreach( $msg['attachments'] as $item ) {
                                    $ticket_info .= '<li><a href="' . $item['url'] . '" target="_blank">' . $item['name'] . '</a></li>';
                                }

                                $ticket_info .= '</ul>
                                                </div>';
                            }

                            $ticket_info .= '</div>';
                        }

                        if( $auth ) {
                        	$ticket_info .= '<div class="ticket_response"><p>' . strip_tags( html_entity_decode( $this->reply_msg ) , '<a><br><br/><b><em><strong><u>' ) . '</p></div>';
                        } else {
                        	$ticket_info = '<div class="ticket_response"><p>You are not authorised to veiw this ticket.</p></div>';
                        }

                        echo $ticket_info;

                    } else {
                        echo '<p>Invalid ticket ID selected - please go back and try again.</p>';
                    }

                break;
            }
		}
	}

	/**
	 * Convert text URLs into HTML links
	 * @param  str $str String of text
	 * @return str String of text with clickable links
	 */
	private function format_links_in_text( $str = false ) {

		if( $str ) {

			$str = preg_replace("#(^|[\n ])([\w]+?://[\w]+[^ \"\n\r\t< ]*)#", "\\1<a href=\"\\2\" target=\"_blank\">\\2</a>", $str);
		    $str = preg_replace("#(^|[\n ])((www|ftp)\.[^ \"\t\n\r< ]*)#", "\\1<a href=\"http://\\2\" target=\"_blank\">\\2</a>", $str);

		    return $str;
		}

		return false;
	}

}