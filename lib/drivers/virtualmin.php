<?php

/*
 +-----------------------------------------------------------------------+
 | lib/drivers/virtualmin.php                                            |
 |                                                                       |
 | Copyright (C) 2014 Julien Poissonnier <julien@lhisp.com>              |
 | Copyright (C) 2014 Xavier Maquil <xavier@lhisp.com>                   |
 | Licensed under the GNU GPL                                            |
 +-----------------------------------------------------------------------+

 +-----------------------------------------------------------------------+
   Sets autoreply in Virtualmin via the LHISP Manage Panel bridge

   Recommended options in vacation config.inc.php:
   $rcmail_config['vacation_gui_vacationdate'] = TRUE;
   $rcmail_config['vacation_gui_vacationsubject'] = TRUE;
   $rcmail_config['vacation_gui_vacationmessage_html'] = TRUE;
 +-----------------------------------------------------------------------+
 */

/*
 * Reads data.
 *
 * @param $data array the array of data to set/get.
 *
 * @return integer the status code.
 */
function vacation_read(array &$data)
{
        $rcmail = rcmail::get_instance();
        $url = $rcmail->config->get('vacation_url');
        $user_pass = $rcmail->config->get('vacation_user_pass');
        $username = $rcmail->user->get_username();

        $curl = curl_init();
        curl_setopt_array($curl, array(
                // Set to FALSE since we're on the same machine and without a proper cert, should be TRUE ideally
                CURLOPT_SSL_VERIFYHOST => FALSE,
                CURLOPT_SSL_VERIFYPEER => FALSE,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_USERPWD => $user_pass,
                CURLOPT_URL => $url.$username,
        ));
        $resp = curl_exec($curl);
        curl_close($curl);

        $autoreply = json_decode($resp, TRUE);
        if (!empty($autoreply)) {
                $start = new DateTime($autoreply['start']);
                $end = new DateTime($autoreply['end']);
                $data['vacation_start'] = $start->getTimestamp();
                $data['vacation_end'] = $end->getTimestamp();
                $data['vacation_message'] = $autoreply['message'];
                $data['vacation_subject'] = $autoreply['subject'];
                $data['vacation_enable'] = TRUE;
        } else {
                $data['vacation_enable'] = FALSE;
        }

        return PLUGIN_SUCCESS;
}

/*
 * Reads data.
 *
 * @param $data array the array of data to set/get.
 *
 * @return integer the status code.
 */
function vacation_write(array &$data)
{
        $rcmail = rcmail::get_instance();
        $url = $rcmail->config->get('vacation_url');
        $user_pass = $rcmail->config->get('vacation_user_pass');
        $username = $rcmail->user->get_username();

        if ($data['vacation_enable']) {
                $data_string = json_encode($data);

        } else {
                $data_string = '{"vacation_enable": false}';
        }

        $curl = curl_init();
        curl_setopt_array($curl, array(
                CURLOPT_SSL_VERIFYHOST => FALSE,
                CURLOPT_SSL_VERIFYPEER => FALSE,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_USERPWD => $user_pass,
                CURLOPT_URL => $url.$username,
                CURLOPT_POSTFIELDS => $data_string,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($data_string))
        ));
        $resp = curl_exec($curl);
        curl_close($curl);
        return PLUGIN_SUCCESS;
}

