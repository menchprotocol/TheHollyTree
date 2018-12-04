<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Comm_model extends CI_Model
{

    function __construct()
    {
        parent::__construct();
    }

    function fb_graph($action, $url, $payload = array())
    {

        //Do some initial checks
        if (!in_array($action, array('GET', 'POST', 'DELETE'))) {

            //Only 4 valid types of $action
            return array(
                'status' => 0,
                'message' => '$action [' . $action . '] is invalid',
            );

        }


        //Start building GET URL:
        if (array_key_exists('access_token', $payload)) {

            //This this access token:
            $access_token_payload = array(
                'access_token' => $payload['access_token'],
            );
            //Remove it just in case:
            unset($payload['access_token']);

        } else {
            //Apply the Page Access Token:
            $fb_settings = $this->config->item('fb_settings');
            $access_token_payload = array(
                'access_token' => $fb_settings['mench_access_token']
            );
        }

        if ($action == 'GET' && count($payload) > 0) {
            //Add $payload to GET variables:
            $access_token_payload = array_merge($access_token_payload, $payload);
            $payload = array();
        }

        $url = 'https://graph.facebook.com/v2.6' . $url;
        $counter = 0;
        foreach ($access_token_payload as $key => $val) {
            $url = $url . ($counter == 0 ? '?' : '&') . $key . '=' . $val;
            $counter++;
        }

        //Make the graph call:
        $ch = curl_init($url);

        //Base setting:
        $ch_setting = array(
            CURLOPT_CUSTOMREQUEST => $action,
            CURLOPT_RETURNTRANSFER => TRUE,
        );

        if (count($payload) > 0) {
            $ch_setting[CURLOPT_HTTPHEADER] = array('Content-Type: application/json; charset=utf-8');
            $ch_setting[CURLOPT_POSTFIELDS] = json_encode($payload);
        }

        //Apply settings:
        curl_setopt_array($ch, $ch_setting);

        //Process results and produce tr_metadata
        $result = objectToArray(json_decode(curl_exec($ch)));
        $tr_metadata = array(
            'action' => $action,
            'payload' => $payload,
            'url' => $url,
            'result' => $result,
        );

        //Did we have any issues?
        if (!$result) {

            //Failed to fetch this profile:
            $error_message = 'Comm_model->fb_graph() failed to ' . $action . ' ' . $url;
            $this->Db_model->tr_create(array(
                'tr_content' => $error_message,
                'tr_en_type_id' => 4246, //Platform Error
                'tr_metadata' => $tr_metadata,
            ));

            //There was an issue accessing this on FB
            return array(
                'status' => 0,
                'message' => $error_message,
                'tr_metadata' => $tr_metadata,
            );

        } else {

            //All seems good, return:
            return array(
                'status' => 1,
                'message' => 'Success',
                'tr_metadata' => $tr_metadata,
            );

        }
    }


    function fb_ref_process($u, $fb_ref)
    {

        if (!$fb_ref || strlen($fb_ref) < 1) {

            return false;

        } elseif (substr_count($fb_ref, 'APSKIP_') == 1) {

            $unsub_value = one_two_explode('APSKIP_', '', $fb_ref);

            if ($unsub_value == 'CANCEL') {

                //User changed their mind, confirm:
                $this->Comm_model->send_message(array(
                    array(
                        'tr_en_child_id' => $u['u_id'],
                        'i_message' => 'Awesome, I am excited to continue helping you to ' . $this->config->item('primary_in_name') . '. ' . echo_pa_lets(),
                    ),
                ));

            } elseif ($unsub_value == 'ALL') {

                //User wants completely out...

                //Skip everything from their Action Plan
                $this->db->query("UPDATE tb_actionplans SET w_status=-1 WHERE w_status>=0 AND w_child_u_id=" . $u['u_id']);
                $intents_skipped = $this->db->affected_rows();

                //Update User communication status:

                $this->Db_model->en_update($u['u_id'], array(
                    'en_communication' => -1, //Unsubscribed
                ));

                //Let them know:
                $this->Comm_model->send_message(array(
                    array(
                        'tr_en_child_id' => $u['u_id'],
                        'i_message' => 'Confirmed, I skipped all ' . $intents_skipped . ' intent' . echo__s($intents_skipped) . ' in your Action Plan. This is the final message you will receive from me unless you message me. Take care of your self and I hope to talk to you soon 😘',
                    ),
                ));

            } elseif (intval($unsub_value) > 0) {

                //User wants to skip a specific intent from their Action Plan, validate it:
                $ws = $this->Db_model->w_fetch(array(
                    'w_id' => intval($unsub_value),
                    'w_status >=' => 0,
                ), array('in'));

                //All good?
                if (count($ws) == 1) {

                    //Update status for this single subscription:
                    $this->db->query("UPDATE tb_actionplans SET w_status=-1 WHERE w_id=" . intval($unsub_value));

                    //Show success message to user:
                    $this->Comm_model->send_message(array(
                        array(
                            'tr_en_child_id' => $u['u_id'],
                            'i_message' => 'I have successfully skipped the intention to ' . $ws[0]['c_outcome'] . '. Say "Unsubscribe" if you wish to stop all future communications. ' . echo_pa_lets(),
                        ),
                    ));

                } else {

                    //let them know we had error:
                    $this->Comm_model->send_message(array(
                        array(
                            'tr_en_child_id' => $u['u_id'],
                            'i_message' => 'Unable to process your request as I could not locate your subscription. Please try again.',
                        ),
                    ));

                    //Log error engagement:
                    $this->Db_model->tr_create(array(
                        'tr_en_creator_id' => $u['u_id'],
                        'tr_content' => 'Failed to skip an intent from the student Action Plan',
                        'tr_en_type_id' => 4246, //System error
                        'e_w_id' => intval($unsub_value),
                    ));

                }
            }

        } elseif (substr_count($fb_ref, 'ACTIVATE_') == 1) {

            if ($fb_ref == 'ACTIVATE_YES') {

                //Update User table status:
                $this->Db_model->u_update($u['u_id'], array(
                    'u_status' => 1,
                ));

                //Inform them:
                $this->Comm_model->send_message(array(
                    array(
                        'tr_en_child_id' => $u['u_id'],
                        'i_message' => 'Sweet, you account is now activated but you are not subscribed to any intents yet. ' . echo_pa_lets(),
                    ),
                ));

            } elseif ($fb_ref == 'ACTIVATE_NO') {

                $this->Comm_model->send_message(array(
                    array(
                        'tr_en_child_id' => $u['u_id'],
                        'i_message' => 'Ok, your account will remain unsubscribed. If you changed your mind, ' . echo_pa_lets(),
                    ),
                ));

            }

        } elseif (substr_count($fb_ref, 'ACTIONPLANADD10_') == 1) {

            //Validate this intent:
            $c_id = intval(one_two_explode('ACTIONPLANADD10_', '', $fb_ref));

            if ($c_id == 0) {

                //They rejected the offer... Acknowledge and give response:
                $this->Comm_model->send_message(array(
                    array(
                        'tr_en_child_id' => $u['u_id'],
                        'i_message' => 'Ok, so how can I help you ' . $this->config->item('primary_in_name') . '? ' . echo_pa_lets(),
                    ),
                ));

            } else {

                $fetch_cs = $this->Db_model->in_fetch(array(
                    'c_id' => $c_id,
                ));

                //Any issues?
                if (count($fetch_cs) < 1) {

                    //Ooops we could not find that C:
                    $this->Comm_model->send_message(array(
                        array(
                            'tr_en_child_id' => $u['u_id'],
                            'i_message' => 'I was unable to locate intent #' . $c_id . ' [' . $fb_ref . ']',
                        ),
                    ));

                } elseif ($fetch_cs[0]['in_status'] < 2) {

                    //Ooops C is no longer active:
                    $this->Comm_model->send_message(array(
                        array(
                            'tr_en_child_id' => $u['u_id'],
                            'i_message' => 'I was unable to subscribe you to ' . $fetch_cs[0]['c_outcome'] . ' as its not published',
                        ),
                    ));

                } else {

                    //Confirm if they are interested for this intention:
                    $this->Comm_model->send_message(array(
                        array(
                            'tr_en_child_id' => $u['u_id'],
                            'tr_in_child_id' => $fetch_cs[0]['c_id'],
                            'i_message' => 'Hello hello 👋 are you interested to ' . $fetch_cs[0]['c_outcome'] . '?',
                            'quick_replies' => array(
                                array(
                                    'content_type' => 'text',
                                    'title' => 'Yes, Learn More',
                                    'payload' => 'ACTIONPLANADD20_' . $fetch_cs[0]['c_id'],
                                ),
                                array(
                                    'content_type' => 'text',
                                    'title' => 'No',
                                    'payload' => 'ACTIONPLANADD10_0',
                                ),
                            ),
                        ),
                    ));

                }
            }

        } elseif (substr_count($fb_ref, 'ACTIONPLANADD20_') == 1) {

            //Initiating an intent Subscription:
            $w_c_id = intval(one_two_explode('ACTIONPLANADD20_', '', $fb_ref));
            $fetch_cs = $this->Db_model->in_fetch(array(
                'c_id' => $w_c_id,
                'in_status >=' => 2,
            ));
            if (count($fetch_cs) == 1) {

                //Intent seems good...
                //See if this intent belong to any of these subscriptions:
                $trs = $this->Db_model->tr_fetch(array(
                    'w_child_u_id' => $u['u_id'], //All subscriptions belonging to this user
                    'w_status >=' => 0, //Any type of past subscription
                    '(cr_parent_c_id=' . $w_c_id . ' OR cr_child_c_id=' . $w_c_id . ')' => null,
                ), array('cr', 'w', 'w_c'));

                if (count($trs) > 0) {

                    //Let the user know that this is a duplicate:
                    $this->Comm_model->send_message(array(
                        array(
                            'tr_en_child_id' => $u['u_id'],
                            'tr_in_child_id' => $fetch_cs[0]['c_id'],
                            'e_w_id' => $trs[0]['k_w_id'],
                            'i_message' => ($trs[0]['c_id'] == $w_c_id ? 'You have already subscribed to ' . $fetch_cs[0]['c_outcome'] . '. We have been working on it together since ' . echo_time($trs[0]['w_timestamp'], 2) . '. /open_actionplan' : 'Your subscription to ' . $trs[0]['c_outcome'] . ' already covers the intention to ' . $fetch_cs[0]['c_outcome'] . ', so I will not create a duplicate subscription. /open_actionplan'),
                        ),
                    ));

                } else {

                    //Now we need to confirm if they really want to subscribe to this...

                    //Fetch all the messages for this intent:
                    $tree = $this->Db_model->c_recursive_fetch($w_c_id, true, false);

                    //Show messages for this intent:
                    $messages = $this->Db_model->i_fetch(array(
                        'i_c_id' => $w_c_id,
                        'i_status >=' => 0, //Published in any form
                    ));

                    foreach ($messages as $i) {
                        $this->Comm_model->send_message(array(
                            array_merge($i, array(
                                'tr_en_child_id' => $u['u_id'],
                            )),
                        ));
                    }

                    //Send message for final confirmation:
                    $this->Comm_model->send_message(array(
                        array(
                            'tr_en_child_id' => $u['u_id'],
                            'tr_in_child_id' => $w_c_id,
                            'i_message' => 'Here is an overview:' . "\n\n" .
                                echo_intent_overview($fetch_cs[0], 1) .
                                echo_contents($fetch_cs[0], 1) .
                                echo_experts($fetch_cs[0], 1) .
                                echo_completion_estimate($fetch_cs[0], 1) .
                                echo_costs($fetch_cs[0], 1) .
                                "\n" . 'Are you ready to ' . $fetch_cs[0]['c_outcome'] . '?',
                            'quick_replies' => array(
                                array(
                                    'content_type' => 'text',
                                    'title' => 'Yes, Subscribe',
                                    'payload' => 'ACTIONPLANADD99_' . $w_c_id,
                                ),
                                array(
                                    'content_type' => 'text',
                                    'title' => 'No',
                                    'payload' => 'ACTIONPLANADD10_0',
                                ),
                                //TODO Maybe Show a "7 Extra Notes" if Drip messages available?
                            ),
                        ),
                    ));

                }
            }

        } elseif (substr_count($fb_ref, 'ACTIONPLANADD99_') == 1) {

            $w_c_id = intval(one_two_explode('ACTIONPLANADD99_', '', $fb_ref));
            //Validate Intent ID:
            $fetch_cs = $this->Db_model->in_fetch(array(
                'c_id' => $w_c_id,
                'in_status >=' => 2,
            ));

            if (count($fetch_cs) == 1) {

                //Add to intent to user's action plan and create a cache of all intent links:
                $w = $this->Db_model->w_create(array(
                    'w_c_id' => $w_c_id,
                    'w_child_u_id' => $u['u_id'],
                ));

                //Was this added successfully?
                if (isset($w['w_id']) && $w['w_id'] > 0) {

                    //Confirm with them that we're now ready:
                    $this->Comm_model->send_message(array(
                        array(
                            'tr_en_child_id' => $u['u_id'],
                            'tr_in_child_id' => $w_c_id,
                            'e_w_id' => $w['w_id'],
                            'i_message' => 'Success! I have added the intention to ' . $fetch_cs[0]['c_outcome'] . ' to your Action Plan 🙌 /open_actionplan',
                        ),
                    ));

                    //Initiate first message for action plan tree:
                    $this->Comm_model->compose_messages(array(
                        'tr_en_child_id' => $u['u_id'],
                        'tr_in_child_id' => $w_c_id,
                        'e_w_id' => $w['w_id'],
                    ), true);

                }
            }

        } elseif (substr_count($fb_ref, 'KCONFIRMEDSKIP_') == 1 || substr_count($fb_ref, 'KSTARTSKIP_') == 1 || substr_count($fb_ref, 'KCANCELSKIP_') == 1) {

            if (substr_count($fb_ref, 'KSTARTSKIP_') == 1) {
                $handler = 'KSTARTSKIP_';
            } elseif (substr_count($fb_ref, 'KCANCELSKIP_') == 1) {
                $handler = 'KCANCELSKIP_';
            } elseif (substr_count($fb_ref, 'KCONFIRMEDSKIP_') == 1) {
                $handler = 'KCONFIRMEDSKIP_';
            }

            //Extract varibales from REF:
            $input_parts = explode('_', one_two_explode($handler, '', $fb_ref));
            $w_id = intval($input_parts[0]);
            $c_id = intval($input_parts[1]);
            $tr_id = intval($input_parts[2]);
            $k_rank = intval($input_parts[3]);


            if (!($w_id > 0 && $c_id > 0 && $tr_id > 0 && $k_rank > 0)) {
                //Log Unknown error:
                $this->Db_model->tr_create(array(
                    'tr_content' => 'fb_ref_process() failed to fetch proper data for ' . $handler . ' request with reference value [' . $fb_ref . ']',
                    'tr_en_type_id' => 4246, //Platform Error
                    'tr_metadata' => $u,
                    'e_w_id' => $w_id,
                    'tr_in_child_id' => $c_id,
                ));
                return false;
            }


            if ($handler == 'KSTARTSKIP_') {

                //User has indicated they want to skip this tree and move on to the next item in-line:
                //Lets confirm the implications of this SKIP to ensure they are aware:

                //See how many children would be skipped if they decide to do so:
                $would_be_skipped = $this->Db_model->k_skip_recursive_down($w_id, $c_id, $tr_id, false);
                $would_be_skipped_count = count($would_be_skipped);

                if ($would_be_skipped_count == 0) {

                    //Nothing found to skip! This should not happen, log error:
                    $this->Db_model->tr_create(array(
                        'tr_content' => 'fb_ref_process() did not find anything to skip for [' . $fb_ref . ']',
                        'tr_en_type_id' => 4246, //Platform Error
                        'tr_metadata' => $u,
                        'e_w_id' => $w_id,
                        'tr_in_child_id' => $c_id,
                    ));

                    //Inform user:
                    $this->Comm_model->send_message(array(
                        array(
                            'tr_en_child_id' => $u['u_id'],
                            'i_message' => 'I did not find anything to skip!',
                        ),
                    ));

                    return false;

                }


                //Construct the message to give more details on skipping:
                $message = 'You are about to skip these ' . $would_be_skipped_count . ' insight' . echo__s($would_be_skipped_count) . ':';
                foreach ($would_be_skipped as $counter => $k_c) {
                    if (strlen($message) < ($this->config->item('fb_max_message') - 200)) {
                        //We have enough room to add more:
                        $message .= "\n\n" . ($counter + 1) . '/ ' . $k_c['c_outcome'];
                    } else {
                        //We cannot add any more, indicate truncating:
                        $remainder = $would_be_skipped_count - $counter;
                        $message .= "\n\n" . 'And ' . $remainder . ' more insight' . echo__s($remainder) . '!';
                        break;
                    }
                }

                //Recommend against it:
                $message .= "\n\n" . 'I would not recommend skipping unless you feel comfortable handling them on your own.';

                //Send them the message:
                $this->Comm_model->send_message(array(
                    array(
                        'tr_en_child_id' => $u['u_id'],
                        'i_message' => $message,
                        'quick_replies' => array(
                            array(
                                'content_type' => 'text',
                                'title' => 'Skip ' . $would_be_skipped_count . ' insight' . echo__s($would_be_skipped_count) . ' 🚫',
                                //Change the reference to indicate their confirmation:
                                'payload' => str_replace('KSTARTSKIP_', 'KCONFIRMEDSKIP_', $fb_ref),
                            ),
                            array(
                                'content_type' => 'text',
                                'title' => 'Continue ▶️',
                                'payload' => str_replace('KSTARTSKIP_', 'KCANCELSKIP_', $fb_ref),
                            ),
                        ),
                    ),
                ));

                //Log engagement:
                $this->Db_model->tr_create(array(
                    'tr_content' => 'User considering to skip ' . $would_be_skipped_count . ' Action Plan intents.',
                    'tr_metadata' => array(
                        'would_be_skipped' => $would_be_skipped,
                        'ref' => $fb_ref,
                    ),
                    'tr_en_creator_id' => $u['u_id'], //user who searched
                    'tr_en_type_id' => 4284, //Skip initiated
                    'tr_in_child_id' => $c_id,
                    'e_w_id' => $w_id,
                ));

            } elseif ($handler == 'KCONFIRMEDSKIP_' || $handler == 'KCANCELSKIP_') {


                if ($handler == 'KCANCELSKIP_') {

                    //user changed their mind and does not want to skip anymore
                    //acknowledge this good decision:
                    $this->Comm_model->send_message(array(
                        array(
                            'tr_en_child_id' => $u['u_id'],
                            'i_message' => 'I am happy you changed your mind! Let\'s continue...',
                        ),
                    ));

                    //Log engagement:
                    $this->Db_model->tr_create(array(
                        'tr_metadata' => array(
                            'ref' => $fb_ref,
                        ),
                        'tr_en_creator_id' => $u['u_id'], //user who searched
                        'tr_en_type_id' => 4285, //Skip cancelled
                        'tr_in_child_id' => $c_id,
                        'e_w_id' => $w_id,
                    ));

                    //Reset ranking to find the next real item:
                    $k_rank = 0;

                } elseif ($handler == 'KCONFIRMEDSKIP_') {

                    //Inform them about the skip status:
                    $this->Comm_model->send_message(array(
                        array(
                            'tr_en_child_id' => $u['u_id'],
                            'tr_in_child_id' => $c_id,
                            'e_w_id' => $w_id,
                            'i_message' => 'Confirmed, I marked this section as skipped. You can always re-visit these insights in your Action Plan and complete them at any time. /open_actionplan',
                        ),
                    ));

                    //Now actually skip and see if we've finished this Action Plan:
                    $skippable_ks = $this->Db_model->k_skip_recursive_down($w_id, $c_id, $tr_id);

                    //Log engagement:
                    $this->Db_model->tr_create(array(
                        'tr_content' => 'Skipping confirmed on ' . count($skippable_ks) . ' Action Plan intents.',
                        'tr_metadata' => array(
                            'ref' => $fb_ref,
                            'skipped' => $skippable_ks,
                        ),
                        'tr_en_creator_id' => $u['u_id'], //user who searched
                        'tr_en_type_id' => 4286, //Skip confirmed
                        'tr_in_child_id' => $c_id,
                        'e_w_id' => $w_id,
                    ));

                }

                //Find the next item to navigate them to:
                $trs_next = $this->Db_model->k_next_fetch($w_id, $k_rank);
                if ($trs_next) {
                    //Now move on to communicate the next step.
                    $this->Comm_model->compose_messages(array(
                        'tr_en_child_id' => $u['u_id'],
                        'tr_in_child_id' => $trs_next[0]['c_id'],
                        'e_w_id' => $w_id,
                    ));
                }

            }

        } elseif (substr_count($fb_ref, 'MARKCOMPLETE_') == 1) {

            //Student consumed AND tree content, and is ready to move on to next intent...
            $input_parts = explode('_', one_two_explode('MARKCOMPLETE_', '', $fb_ref));
            $w_id = intval($input_parts[0]);
            $tr_id = intval($input_parts[1]);
            $k_rank = intval($input_parts[2]);
            if ($w_id > 0 && $tr_id > 0 && $k_rank > 0) {

                //Fetch child intent first to check requirements:
                $k_children = $this->Db_model->tr_fetch(array(
                    'w_id' => $w_id,
                    'tr_id' => $tr_id,
                ), array('w', 'cr', 'cr_c_child'));

                //Do we need any additional information?
                $requirement_notes = echo_c_requirements($k_children[0], true);
                if ($requirement_notes) {

                    //yes do, let them know that they can only complete via the Action Plan:
                    $this->Comm_model->send_message(array(
                        array(
                            'tr_en_child_id' => $u['u_id'],
                            'tr_in_child_id' => $k_children[0]['c_id'],
                            'e_w_id' => $w_id,
                            'i_message' => $requirement_notes,
                        ),
                    ));

                } else {

                    //Fetch parent intent to mark as complete:
                    $k_parents = $this->Db_model->tr_fetch(array(
                        'w_id' => $w_id,
                        'tr_id' => $tr_id,
                    ), array('w', 'cr', 'cr_c_parent'));

                    //No requirements, Update this intent and move on:
                    $this->Db_model->k_complete_recursive_up($k_parents[0], $k_parents[0]);

                    //Go to next item:
                    $trs_next = $this->Db_model->k_next_fetch($w_id);
                    if ($trs_next) {
                        //Now move on to communicate the next step.
                        $this->Comm_model->compose_messages(array(
                            'tr_en_child_id' => $u['u_id'],
                            'tr_in_child_id' => $trs_next[0]['c_id'],
                            'e_w_id' => $w_id,
                        ));
                    }
                }
            }

        } elseif (substr_count($fb_ref, 'CHOOSEOR_') == 1) {

            //Student has responded to a multiple-choice OR tree
            $input_parts = explode('_', one_two_explode('CHOOSEOR_', '', $fb_ref));
            $w_id = intval($input_parts[0]);
            $cr_parent_c_id = intval($input_parts[1]);
            $c_id = intval($input_parts[2]);
            $k_rank = intval($input_parts[3]);

            if (!($w_id > 0 && $cr_parent_c_id > 0 && $c_id > 0 && $k_rank > 0)) {
                //Log Unknown error:
                $this->Db_model->tr_create(array(
                    'tr_content' => 'fb_ref_process() failed to fetch proper data for CHOOSEOR_ request with reference value [' . $fb_ref . ']',
                    'tr_en_type_id' => 4246, //Platform Error
                    'tr_metadata' => $u,
                    'e_w_id' => $w_id,
                    'tr_in_child_id' => $c_id,
                ));
                return false;
            }

            //Confirm answer received:
            $this->Comm_model->send_message(array(
                array(
                    'tr_en_child_id' => $u['u_id'],
                    'tr_in_child_id' => $c_id,
                    'e_w_id' => $w_id,
                    'i_message' => echo_pa_saved(),
                ),
            ));

            //Now save answer:
            if ($this->Db_model->k_choose_or($w_id, $cr_parent_c_id, $c_id)) {
                //Find the next item to navigate them to:
                $trs_next = $this->Db_model->k_next_fetch($w_id, $k_rank);
                if ($trs_next) {
                    //Now move on to communicate the next step.
                    $this->Comm_model->compose_messages(array(
                        'tr_en_child_id' => $u['u_id'],
                        'tr_in_child_id' => $trs_next[0]['c_id'],
                        'e_w_id' => $w_id,
                    ));
                }
            }

        }
    }

    function fb_message_process($u, $fb_message_received)
    {

        if (!$fb_message_received) {
            return false;
        }

        $c_target_outcome = null;
        if ($fb_message_received) {
            $fb_message_received = trim(strtolower($fb_message_received));
            if (substr_count($fb_message_received, 'lets ') > 0) {
                $c_target_outcome = one_two_explode('lets ', '', $fb_message_received);
            } elseif (substr_count($fb_message_received, 'let’s ') > 0) {
                $c_target_outcome = one_two_explode('let’s ', '', $fb_message_received);
            } elseif (substr_count($fb_message_received, 'let\'s ') > 0) {
                $c_target_outcome = one_two_explode('let\'s ', '', $fb_message_received);
            } elseif (substr($fb_message_received, -1) == '?') {
                //Them seem to be asking a question, lets treat this as a command:
                $c_target_outcome = str_replace('?', '', $fb_message_received);
            }
        }


        if (includes_any($fb_message_received, array('unsubscribe', 'quit', 'skip'))) {

            if ($u['en_communication'] < 0) {
                //User is already unsubscribed, let them know:
                return $this->Comm_model->send_message(array(
                    array(
                        'tr_en_child_id' => $u['u_id'],
                        'i_message' => 'You are already unsubscribed from Mench and will no longer receive any communication from us. To subscribe again, ' . echo_pa_lets(),
                    ),
                ));
            }

            //List their Action Plan intents:
            $actionplans = $this->Db_model->tr_fetch(array(
                'tr_en_type_id' => 4235, //Intents added to the action plan
                'tr_en_child_id' => $u['u_id'], //Belongs to this user
                'tr_in_parent_id' => 0, //This indicates that this is a top-level intent in the Action Plan
                'tr_status IN (0,1,2)' => null, //Actively working on
            ), 100, array('in_child'), array('tr_rank' => 'ASC'));


            //Do they have anything in their Action Plan?
            if (count($actionplans) > 0) {

                $quick_replies = array();
                $i_message = 'Choose the intention you like to skip:';
                $increment = 1;

                foreach ($actionplans as $counter => $li) {
                    //Construct unsubscribe confirmation body:
                    $i_message .= "\n\n" . '/' . ($counter + $increment) . ' Skip ' . $li['c_outcome'];
                    array_push($quick_replies, array(
                        'content_type' => 'text',
                        'title' => '/' . ($counter + $increment),
                        'payload' => 'APSKIP_' . $li['c_id'],
                    ));
                }

                if (count($actionplans) >= 2) {
                    //Give option to skip all and unsubscribe:
                    $increment++;
                    $i_message .= "\n\n" . '/' . ($counter + $increment) . ' Skip all intentions and unsubscribe';
                    array_push($quick_replies, array(
                        'content_type' => 'text',
                        'title' => '/' . ($counter + $increment),
                        'payload' => 'APSKIP_ALL',
                    ));
                }

                //Alwyas give none option:
                $increment++;
                $i_message .= "\n\n" . '/' . ($counter + $increment) . ' Cancel skipping and continue';
                array_push($quick_replies, array(
                    'content_type' => 'text',
                    'title' => '/' . ($counter + $increment),
                    'payload' => 'APSKIP_CANCEL',
                ));

                //Send out message and let them confirm:
                $this->Comm_model->send_message(array(
                    array(
                        'tr_en_child_id' => $u['u_id'],
                        'i_message' => $i_message,
                        'quick_replies' => $quick_replies,
                    ),
                ));

            } else {

                //They do not have anything in their Action Plan, so we assume they just want to Unsubscribe and stop all future communications:
                $this->Comm_model->send_message(array(
                    array(
                        'tr_en_child_id' => $u['u_id'],
                        'i_message' => 'Got it, just to confirm, you want to unsubscribe and stop all future communications with me?',
                        'quick_replies' => array(
                            array(
                                'content_type' => 'text',
                                'title' => 'Yes, Unsubscribe',
                                'payload' => 'APSKIP_ALL',
                            ),
                            array(
                                'content_type' => 'text',
                                'title' => 'No, Stay Friends',
                                'payload' => 'APSKIP_CANCEL',
                            ),
                        ),
                    ),
                ));

            }

        } elseif ($fb_message_received && $u['en_communication'] < 0) {

            //We got a message from an unsubscribed user, let them know:
            return $this->Comm_model->send_message(array(
                array(
                    'tr_en_child_id' => $u['u_id'],
                    'i_message' => 'You are currently unsubscribed. Would you like me to re-activate your account?',
                    'quick_replies' => array(
                        array(
                            'content_type' => 'text',
                            'title' => 'Yes, Re-Activate',
                            'payload' => 'ACTIVATE_YES',
                        ),
                        array(
                            'content_type' => 'text',
                            'title' => 'Stay Unsubscribed',
                            'payload' => 'ACTIVATE_NO',
                        ),
                    ),
                ),
            ));

        } elseif ($c_target_outcome) {

            //Do a search to see what we find...
            $search_index = load_php_algolia('alg_intents');
            $res = $search_index->search($c_target_outcome, [
                'hitsPerPage' => 6,
                'filters' => 'in_status>=2', //Search published intents
            ]);

            //Log intent search:
            $this->Db_model->tr_create(array(
                'tr_content' => 'Found ' . $res['nbHits'] . ' intent' . echo__s($res['nbHits']) . ' matching "' . $c_target_outcome . '"',
                'tr_metadata' => array(
                    'input_data' => $c_target_outcome,
                    'output' => $res,
                ),
                'tr_en_creator_id' => $u['u_id'], //user who searched
                'tr_en_type_id' => 4275, //Search for New Intent Subscription
            ));

            //Check to see if we have a single result without any children:
            if ($res['nbHits'] == 1 && $res['hits'][0]['in__tree_count'] == 1) {

                //Yes, just send the messages of this intent as the response:


            } elseif ($res['nbHits'] > 0) {

                //Show options for them to subscribe to:
                $quick_replies = array();
                $i_message = 'I found these intents:';

                foreach ($res['hits'] as $count => $hit) {
                    $i_message .= "\n\n" . ($count + 1) . '/ ' . $hit['c_outcome'] . ' in ' . strip_tags(echo_hour_range($hit));
                    array_push($quick_replies, array(
                        'content_type' => 'text',
                        'title' => ($count + 1) . '/',
                        'payload' => 'ACTIONPLANADD20_' . $hit['c_id'],
                    ));
                }

                //Give them a none option:
                $i_message .= "\n\n" . ($count + 2) . '/ None of the above';
                array_push($quick_replies, array(
                    'content_type' => 'text',
                    'title' => ($count + 2) . '/',
                    'payload' => 'ACTIONPLANADD10_0',
                ));

                //return what we found to the student to decide:
                $this->Comm_model->send_message(array(
                    array(
                        'tr_en_child_id' => $u['u_id'],
                        'i_message' => $i_message,
                        'quick_replies' => $quick_replies,
                    ),
                ));

            } else {

                //Respond to user:
                $this->Comm_model->send_message(array(
                    array(
                        'tr_en_child_id' => $u['u_id'],
                        'i_message' => 'Got it! I have made a note on empowering you to "' . $c_target_outcome . '". I will let you know as soon as I am trained on this. Is there anything else I can help you with right now?',
                    ),
                ));

                //Create new intent in the suggestion bucket:
                //$this->Db_model->c_new(000, $c_target_outcome, 0, 2, $u['u_id']);

            }


            //See if an admin has sent this user a message in the last hour via the Facebook Inbox UI:
        } elseif (count($this->Db_model->tr_fetch(array(
                'tr_timestamp >=' => date("Y-m-d H:i:s", (time() - (1800))), //Messages sent from us less than 30 minutes ago
                'tr_en_type_id' => 4280, //Messages sent from us
                'tr_en_creator_id' => 4148, //We log Facebook Inbox UI messages sent with this entity ID
            ), 1)) == 0) {

            //Fetch their currently working on subscriptions:
            $actionplans = $this->Db_model->w_fetch(array(
                'w_child_u_id' => $u['u_id'],
                'w_status' => 1, //Working on...
            ));

            if (count($actionplans) == 0) {

                //There is nothing in their Action plan that they are working on!

                //Log engagement:
                $this->Db_model->tr_create(array(
                    'tr_content' => $fb_message_received,
                    'tr_en_type_id' => 4287, //Log Unrecognizable Message Received
                    'tr_en_creator_id' => $u['u_id'], //User who initiated this message
                ));

                //Recommend to subscribe to our default intent:
                $this->Comm_model->fb_ref_process($u, 'ACTIONPLANADD10_' . $this->config->item('primary_in_id'));

            } elseif (in_array($fb_message_received, array('yes', 'yeah', 'ya', 'ok', 'continue', 'ok continue', 'ok continue ▶️', '▶️', 'ok continue', 'go', 'yass', 'yas', 'yea', 'yup', 'next', 'yes, learn more'))) {

                //Accepting an offer...

            } elseif (in_array($fb_message_received, array('skip', 'skip it'))) {


            } elseif (in_array($fb_message_received, array('help', 'support', 'f1', 'sos'))) {

                //Ask the user if they like to be connected to a human
                //IF yes, create a ATTENTION NEEDED engagement that would notify admin so admin can start a manual conversation

            } elseif (in_array($fb_message_received, array('learn', 'learn more', 'explain', 'explain more'))) {


            } elseif (in_array($fb_message_received, array('no', 'nope', 'nah', 'cancel', 'stop'))) {
                //Rejecting an offer...

            } elseif (substr($fb_message_received, 0, 1) == '/' || is_int($fb_message_received)) {
                //Likely an OR response with a specific number in mind...

            } else {

                //We don't know what this message means!
                //TODO Optimize for multiple subscriptions, this one only deals with the first randomly selected one...

                //Log engagement:
                $this->Db_model->tr_create(array(
                    'tr_content' => $fb_message_received,
                    'tr_en_type_id' => 4287, //Log Unrecognizable Message Received
                    'tr_en_creator_id' => $u['u_id'], //User who initiated this message
                    'e_w_id' => $actionplans[0]['w_id'],
                ));

                //Notify the user that we don't understand:
                $this->Comm_model->send_message(array(
                    array(
                        'tr_en_child_id' => $u['u_id'],
                        'i_message' => echo_pa_oneway(),
                    ),
                ));

                //Remind user of their next step, if any:
                $trs_next = $this->Db_model->k_next_fetch($actionplans[0]['w_id']);
                if ($trs_next) {
                    $this->Comm_model->compose_messages(array(
                        'tr_en_child_id' => $u['u_id'],
                        'tr_in_child_id' => $trs_next[0]['c_id'],
                        'e_w_id' => $actionplans[0]['w_id'],
                    ));
                }

            }
        }
    }


    function fb_identify_activate($fp_psid)
    {

        /*
         *
         * Function will detect the entity (user) ID for all FB webhook calls
         *
         */

        if ($fp_psid < 1) {
            //Ooops, this is not good:
            $this->Db_model->tr_create(array(
                'tr_content' => 'fb_identify_activate() got called without $fp_psid variable',
                'tr_en_type_id' => 4246, //Platform Error
            ));
            return false;
        }

        //Try finding user references... Is this psid already registered?
        //We either have the user in DB or we'll register them now:
        $fetch_us = $this->Db_model->en_fetch(array(
            'u_fb_psid' => $fp_psid,
        ), array('skip_en__parents'));


        if (count($fetch_us) > 0) {
            //User found:
            return $fetch_us[0];
        }


        //This is a new user that needs to be registered!
        //Call facebook messenger API and get user profile
        $graph_fetch = $this->Comm_model->fb_graph('GET', '/' . $fp_psid, array());


        //Did we find the profile from FB?
        if (!$graph_fetch['status'] || !isset($graph_fetch['tr_metadata']['result']['first_name']) || strlen($graph_fetch['tr_metadata']['result']['first_name']) < 1) {

            //No profile!
            //This happens when user has signed uo to messenger with their phone number or for any reason that Facebook does not provide profile details
            $en = $this->Db_model->u_create(array(
                'u_full_name' => 'Candidate ' . rand(100000000, 999999999),
                'u_fb_psid' => $fp_psid,
            ));

            //Inform the user:
            $this->Comm_model->send_message(array(
                array(
                    'tr_en_child_id' => $u['u_id'],
                    'i_message' => 'Hi stranger! Let\'s get started by completing your profile information by opening the My Account tab in the menu below.',
                ),
            ));

        } else {

            //We did find the profile, move ahead:
            $fb_profile = $graph_fetch['tr_metadata']['result'];

            //Split locale into language and country
            $locale = explode('_', $fb_profile['locale'], 2);

            //Create user
            $en = $this->Db_model->u_create(array(
                'u_full_name' => $fb_profile['first_name'] . ' ' . $fb_profile['last_name'],
                'u_timezone' => $fb_profile['timezone'],
                'u_gender' => strtolower(substr($fb_profile['gender'], 0, 1)),
                'u_language' => $locale[0],
                'u_country_code' => $locale[1],
                'u_fb_psid' => $fp_psid,
            ));

        }

        //Assign people group as we know this is who they are:
        $ur1 = $this->Db_model->ur_create(array(
            'ur_child_u_id' => $u['u_id'],
            'ur_parent_u_id' => 1278,
        ));

        //Log new user engagement:
        $this->Db_model->tr_create(array(
            'tr_en_creator_id' => $u['u_id'],
            'tr_en_type_id' => 4265, //User Joined
            'tr_metadata' => $u,
        ));

        //Update Algolia:
        $this->Db_model->algolia_sync('en', $u['u_id']);

        //Save picture locally:
        $this->Db_model->tr_create(array(
            'tr_en_creator_id' => $u['u_id'],
            'tr_content' => $fb_profile['profile_pic'], //Image to be saved
            'tr_status' => 0, //Pending upload
            'tr_en_type_id' => 4299, //Save media file to Mench cloud
        ));

        //Return user object:
        return $u;

    }


    function send_message($messages)
    {

        if (count($messages) < 1) {
            return array(
                'status' => 0,
                'message' => 'No messages set',
            );
        }

        $failed_count = 0;
        $email_to_send = array();
        $tr_metadata = array(
            'messages' => array(),
            'email' => array(),
        );

        foreach ($messages as $message) {

            //Make sure we have the necessary fields:
            if (!isset($message['tr_en_child_id'])) {

                //Log error:
                $this->Db_model->tr_create(array(
                    'tr_metadata' => $message,
                    'tr_en_type_id' => 4246, //Platform error
                    'tr_content' => 'send_message() failed to send message as it was missing  tr_en_child_id',
                ));
                continue;

            }

            //TODO Implement simple caching to remember $dispatch_fp_psid && $en IF some details remain the same
            if (1) {

                //Fetch user communication preferences:
                $entities = array();

                if (count($entities) < 1) {
                    //Fetch user profile via their account:
                    $entities = $this->Db_model->en_fetch(array(
                        'u_id' => $message['tr_en_child_id'],
                    ));
                }

                if (count($entities) < 1) {

                    //Log error:
                    $failed_count++;
                    $this->Db_model->tr_create(array(
                        'tr_en_child_id' => $message['tr_en_child_id'],
                        'tr_metadata' => $message,
                        'tr_en_type_id' => 4246, //Platform error
                        'tr_content' => 'send_message() failed to fetch user details message as it was missing core variables',
                    ));
                    continue;

                } else {

                    //Determine communication method:
                    $dispatch_fp_psid = 0;
                    $en = array();

                    if ($entities[0]['u_fb_psid'] > 0) {
                        //We fetched an subscription with an active Messenger connection:
                        $dispatch_fp_psid = $entities[0]['u_fb_psid'];
                        $en = $entities[0];
                    } elseif (strlen($entities[0]['u_email']) > 0 && filter_var($entities[0]['u_email'], FILTER_VALIDATE_EMAIL)) {
                        //User has not activated Messenger but has email:
                        $en = $entities[0];
                    } else {

                        //This should technically not happen!
                        //Log error:
                        $failed_count++;
                        $this->Db_model->tr_create(array(
                            'tr_en_child_id' => $message['tr_en_child_id'],
                            'tr_metadata' => $message,
                            'tr_en_type_id' => 4246, //Platform error
                            'tr_content' => 'send_message() detected user without an active email/Messenger',
                        ));
                        continue;

                    }
                }
            }


            //Send using email or Messenger?
            if ($dispatch_fp_psid) {

                $u_fb_notifications = echo_status('u_fb_notification');

                //Prepare Payload:
                $payload = array(
                    'recipient' => array(
                        'id' => $dispatch_fp_psid,
                    ),
                    'message' => echo_i($message, $u['u_full_name'], true),
                    'messaging_type' => 'NON_PROMOTIONAL_SUBSCRIPTION', //https://developers.facebook.com/docs/messenger-platform/send-messages#messaging_types
                    // TODO fetch from u_fb_notification & translate 'notification_type' => $u_fb_notifications[$w['u_fb_notification']]['s_fb_key'],
                );

                //Messenger:
                $process = $this->Comm_model->fb_graph('POST', '/me/messages', $payload);

                //Log Child Message Engagement:
                $this->Db_model->tr_create(array(
                    'tr_en_creator_id' => (isset($message['tr_en_creator_id']) ? $message['tr_en_creator_id'] : 0),
                    'tr_en_child_id' => (isset($message['tr_en_child_id']) ? $message['tr_en_child_id'] : 0),
                    'tr_content' => $message['i_message'],
                    'tr_metadata' => array(
                        'input_message' => $message,
                        'payload' => $payload,
                        'results' => $process,
                    ),
                    'tr_en_type_id' => 4280, //Child message
                    'e_i_id' => (isset($message['i_id']) ? $message['i_id'] : 0), //The message that is being dripped
                    'tr_in_child_id' => (isset($message['i_c_id']) ? $message['i_c_id'] : 0),
                ));

                if (!$process['status']) {
                    $failed_count++;
                }

                array_push($tr_metadata['messages'], $process);

            } else {

                //This is an email request, combine the emails per user:
                if (!isset($email_to_send[$u['u_id']])) {

                    $subject_line = 'New Message from Mench';

                    $email_variables = array(
                        'u_email' => $u['u_email'],
                        'subject_line' => $subject_line,
                        'html_message' => echo_i($message, $u['u_full_name'], false),
                    );


                    $e_var_create = array(
                        'e_var_create' => array(
                            'tr_en_creator_id' => (isset($message['tr_en_creator_id']) ? $message['tr_en_creator_id'] : 0), //If set...
                            'tr_en_child_id' => $u['u_id'],
                            'tr_content' => $email_variables['subject_line'],
                            'tr_metadata' => $email_variables,
                            'tr_en_type_id' => 4276, //Email message sent
                            'tr_in_child_id' => (isset($message['i_c_id']) ? $message['i_c_id'] : 0),
                        ),
                    );

                    $email_to_send[$u['u_id']] = array_merge($email_variables, $e_var_create);

                } else {
                    //Append message to this user:
                    $email_to_send[$u['u_id']]['html_message'] .= '<div style="padding-top:12px;">' . echo_i($message, $u['u_full_name'], false) . '</div>';
                }

            }
        }


        //Do we have to send message?
        if (count($email_to_send) > 0) {
            //Yes, go through these emails and send them:
            foreach ($email_to_send as $email) {
                $process = $this->Comm_model->send_email(array($email['u_email']), $email['subject_line'], $email['html_message'], $email['e_var_create'], 'support@mench.com' /*Hack! To be replaced with ceo email*/);

                array_push($tr_metadata['email'], $process);
            }
        }


        if ($failed_count > 0) {

            return array(
                'status' => 0,
                'message' => 'Failed to send ' . $failed_count . '/' . count($messages) . ' message' . echo__s(count($messages)) . '.',
                'tr_metadata' => $tr_metadata,
            );

        } else {

            return array(
                'status' => 1,
                'message' => 'Successfully sent ' . count($messages) . ' message' . echo__s(count($messages)),
                'tr_metadata' => $tr_metadata,
            );

        }
    }

    function compose_messages($e, $skip_messages = false)
    {

        //Validate key components that are required:
        $error_message = null;
        if (count($e) < 1) {
            $error_message = 'Missing $e';
        } elseif (!isset($e['tr_in_child_id']) || $e['tr_in_child_id'] < 1) {
            $error_message = 'Missing tr_in_child_id';
        } elseif (!isset($e['tr_en_child_id']) || $e['tr_en_child_id'] < 1) {
            $error_message = 'Missing  tr_en_child_id';
        }

        if (!$error_message) {

            //Fetch intent and its messages with an appropriate depth
            $intents = $this->Db_model->in_fetch(array(
                'c_id' => $e['tr_in_child_id'],
            ), 0, array('in__active_messages')); //Supports up to 2 levels deep for now...

            //Check to see if we have any other errors:
            if (!isset($intents[0])) {
                $error_message = 'Invalid Intent ID [' . $e['tr_in_child_id'] . ']';
            } else {
                //Check the required notes as we'll use this later:
                $requirement_notes = echo_c_requirements($intents[0], true);
            }
        }

        //Did we catch any errors?
        if ($error_message) {
            //Log error:
            $this->Db_model->tr_create(array(
                'tr_content' => 'compose_messages() error: ' . $error_message,
                'tr_en_type_id' => 4246, //Platform Error
                'tr_metadata' => $e,
                'tr_en_child_id' => $e['tr_en_child_id'],
                'tr_in_child_id' => $e['tr_in_child_id'],
                'tr_en_creator_id' => $e['tr_en_creator_id'],
            ));

            //Return error:
            return array(
                'status' => 0,
                'message' => $error_message,
            );
        }


        //Let's start adding-up the instant messages:
        $instant_messages = array();

        //Give some context on the current intent:
        if (isset($e['e_w_id']) && $e['e_w_id'] > 0) {

            //Lets see how many child intents there are
            $k_outs = $this->Db_model->tr_fetch(array(
                'w_id' => $e['e_w_id'],
                'w_status IN (0,1)' => null, //Active subscriptions only
                'cr_parent_c_id' => $e['tr_in_child_id'],
                //We are fetching with any k_status just to see what is available/possible from here
            ), array('w', 'cr', 'cr_c_child'));

            if (count($k_outs) > 0 && !($k_outs[0]['w_c_id'] == $e['tr_in_child_id'])) {
                //Only confirm the intention if its not the top-level action plan intention:
                array_push($instant_messages, array(
                    'tr_en_child_id' => $e['tr_en_child_id'],
                    'tr_in_child_id' => $e['tr_in_child_id'],
                    'e_w_id' => $e['e_w_id'],
                    'i_message' => 'Let’s ' . $intents[0]['c_outcome'] . '.',
                ));
            }

        }


        //Append main object messages:
        if (!$skip_messages && isset($intents[0]['in__active_messages']) && count($intents[0]['in__active_messages']) > 0) {
            //We have messages for the very first level!
            foreach ($intents[0]['in__active_messages'] as $key => $i) {
                if ($i['i_status'] == 1) {
                    //Add message to instant stream:
                    array_push($instant_messages, array_merge($e, $i));
                }
            }
        }


        //Do we have a subscription, if so, we need to add a next step message:
        if ($requirement_notes) {

            //URL or a written response is required, let them know that they should complete using the Action Plan:
            array_push($instant_messages, array(
                'tr_en_child_id' => $e['tr_en_child_id'],
                'tr_in_child_id' => $e['tr_in_child_id'],
                'e_w_id' => $e['e_w_id'],
                'i_message' => $requirement_notes,
            ));

        } elseif (isset($e['e_w_id']) && $e['e_w_id'] > 0) {

            $message = null;
            $quick_replies = array();

            //Nothing is required to mark as complete, which means we can move forward with this:
            //How many children do we have for this intent?
            if (count($k_outs) <= 1) {

                //We have 0-1 child intents! If zero, let's see what the next step:
                if (count($k_outs) == 0) {
                    //Let's try to find the next item in tree:
                    $k_outs = $this->Db_model->k_next_fetch($e['e_w_id']);
                }

                //Do we have a next intent?
                if (count($k_outs) > 0 && !($k_outs[0]['c_id'] == $intents[0]['c_id'])) {

                    //Give option to move on:
                    $message .= 'The next step to ' . $intents[0]['c_outcome'] . ' is to ' . $k_outs[0]['c_outcome'] . '.';
                    array_push($quick_replies, array(
                        'content_type' => 'text',
                        'title' => 'Ok Continue ▶️',
                        'payload' => 'MARKCOMPLETE_' . $e['e_w_id'] . '_' . $k_outs[0]['tr_id'] . '_' . $k_outs[0]['k_rank'], //Here are are using MARKCOMPLETE_ also for OR branches with a single option... Maybe we need to change this later?! For now it feels ok to do so...
                    ));

                }

            } else {

                //We have multiple children that are pending completion...
                //Is it ALL or ANY?
                if (intval($intents[0]['in_is_any'])) {

                    //Note that ANY nodes cannot require a written response or a URL
                    //User needs to choose one of the following:
                    $message .= 'Choose one of these ' . count($k_outs) . ' options to ' . $intents[0]['c_outcome'] . ':';
                    foreach ($k_outs as $counter => $k) {
                        if ($counter == 10) {
                            break; //Quick reply accepts 11 options max!
                            //We know that the $message length cannot surpass the limit defined by fb_max_message variable!
                        }
                        $message .= "\n\n" . ($counter + 1) . '/ ' . $k['c_outcome'];
                        array_push($quick_replies, array(
                            'content_type' => 'text',
                            'title' => '/' . ($counter + 1),
                            'payload' => 'CHOOSEOR_' . $e['e_w_id'] . '_' . $e['tr_in_child_id'] . '_' . $k['c_id'] . '_' . $k['k_rank'],
                        ));
                    }

                } else {

                    //User needs to complete all children, and we'd recommend the first item as their next step:
                    $message .= 'There are ' . count($k_outs) . ' steps to ' . $intents[0]['c_outcome'] . ':';
                    foreach ($k_outs as $counter => $k) {

                        if ($counter == 0) {
                            array_push($quick_replies, array(
                                'content_type' => 'text',
                                'title' => 'Start Step 1 ▶️',
                                'payload' => 'MARKCOMPLETE_' . $e['e_w_id'] . '_' . $k['tr_id'] . '_' . $k['k_rank'],
                            ));
                        }

                        //make sure message is within range:
                        if (strlen($message) < ($this->config->item('fb_max_message') - 200)) {
                            //Add message:
                            $message .= "\n\n" . 'Step ' . ($counter + 1) . ': ' . $k['c_outcome'];
                        } else {
                            //We cannot add any more, indicate truncating:
                            $remainder = count($k_outs) - $counter;
                            $message .= "\n\n" . 'And ' . $remainder . ' more step' . echo__s($remainder) . '!';
                            break;
                        }
                    }

                }


                //As long as $e['tr_in_child_id'] is NOT equal to w_c_id, then we will have a k_out relation so we can give the option to skip:
                $k_ins = $this->Db_model->tr_fetch(array(
                    'w_id' => $e['e_w_id'],
                    'w_status IN (0,1)' => null, //Active subscriptions only
                    'cr_child_c_id' => $e['tr_in_child_id'],
                ), array('w', 'cr', 'cr_c_child'));


                if (count($k_ins) > 0) {
                    //Give option to skip if NOT the main intent of the subscription:
                    array_push($quick_replies, array(
                        'content_type' => 'text',
                        'title' => 'Skip',
                        'payload' => 'KSTARTSKIP_' . $e['e_w_id'] . '_' . $e['tr_in_child_id'] . '_' . $k_ins[0]['tr_id'] . '_' . $k_ins[0]['k_rank'],
                    ));
                }
            }

            //Append next-step message:
            array_push($instant_messages, array(
                'tr_en_child_id' => $e['tr_en_child_id'],
                'tr_in_child_id' => $e['tr_in_child_id'],
                'e_w_id' => $e['e_w_id'],
                'i_message' => $message,
                'quick_replies' => $quick_replies,
            ));

        }


        //Anything to be sent instantly?
        if (count($instant_messages) < 1) {
            //Nothing to be sent
            return array(
                'status' => 0,
                'message' => 'No messages to be sent',
            );
        }

        //All good, attempt to Dispatch all messages, their engagements have already been logged:
        return $this->Comm_model->send_message($instant_messages);

    }

    function send_email($to_array, $subject, $html_message, $e_var_create = array(), $reply_to = null)
    {

        if (is_dev()) {
            return true;
        }

        //Loadup amazon SES:
        require_once('application/libraries/aws/aws-autoloader.php');
        $this->CLIENT = new Aws\Ses\SesClient([
            'version' => 'latest',
            'region' => 'us-west-2',
            'credentials' => $this->config->item('aws_credentials'),
        ]);

        if (!$reply_to) {
            //Set default:
            $reply_to = 'support@mench.com';
        }

        //Log engagement once:
        if (count($e_var_create) > 0) {
            $this->Db_model->tr_create($e_var_create);
        }

        return $this->CLIENT->sendEmail(array(
            // Source is required
            'Source' => 'support@mench.com',
            // Destination is required
            'Destination' => array(
                'ToAddresses' => $to_array,
                'CcAddresses' => array(),
                'BccAddresses' => array(),
            ),
            // Message is required
            'Message' => array(
                // Subject is required
                'Subject' => array(
                    // Data is required
                    'Data' => $subject,
                    'Charset' => 'UTF-8',
                ),
                // Body is required
                'Body' => array(
                    'Text' => array(
                        // Data is required
                        'Data' => strip_tags($html_message),
                        'Charset' => 'UTF-8',
                    ),
                    'Html' => array(
                        // Data is required
                        'Data' => $html_message,
                        'Charset' => 'UTF-8',
                    ),
                ),
            ),
            'ReplyToAddresses' => array($reply_to),
            'ReturnPath' => 'support@mench.com',
        ));

    }

}