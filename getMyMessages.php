<?php
/* Configure Credentials */
    $Credentials = array(
        'authToken' => '',
        'devId'     => '',
        'appId'     => '',
        'certId'    => '',
        'url'       => ''
    );

/**DB configuration */
    $_host = "localhost";
    $_username = "root";
    $_password = "";
    $_database = "world4x4_develop";
    $con = new mysqli($_host, $_username, $_password, $_database);
    if(mysqli_connect_error()) { echo "db connection error"; exit; }

function request($theData, $headers, $url){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $theData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $xml = curl_exec($ch);
    curl_close($ch);

    $Json = json_encode(simplexml_load_string($xml, "SimpleXMLElement", LIBXML_NOCDATA));
    $data =json_decode($Json);
    return $data;
}

/*
    Get messages from my inbox
    
    @variables
    $StartCreationTime :  start time 
    $EndCreationTime   :  end time
    $pageNumber        :  pagenation number
    $totalPageNumber   :  total pagenation number of inbox

    @functions
    send_megetMemberMessages  : function for get messages from api
    save_to_db                : function for save message data to db  
*/

function getMemberMessages($Credentials, $StartCreationTime, $EndCreationTime, $pageNumber){
 
    $theData = '<?xml version="1.0" encoding="utf-8"?>
                <GetMemberMessagesRequest xmlns="urn:ebay:apis:eBLBaseComponents">
                <RequesterCredentials>
                    <eBayAuthToken>'.$Credentials['authToken'].'</eBayAuthToken>
                </RequesterCredentials>
                <WarningLevel>High</WarningLevel>
                <MailMessageType>All</MailMessageType>
                <MessageStatus>Unanswered</MessageStatus>
                <StartCreationTime>'.$StartCreationTime.'</StartCreationTime>
                <EndCreationTime>'.$EndCreationTime.'</EndCreationTime>
                <Pagination>
                    <EntriesPerPage>5</EntriesPerPage>
                    <PageNumber>'.$pageNumber.'</PageNumber>
                </Pagination>
                </GetMemberMessagesRequest>';

    $headers = array(
        'Content-Type: text/xml',
        'X-EBAY-API-COMPATIBILITY-LEVEL: 1047',
        'X-EBAY-API-DEV-NAME:'.$Credentials['devId'],
        'X-EBAY-API-APP-NAME:'.$Credentials['appId'],
        'X-EBAY-API-CERT-NAME:'.$Credentials['certId'],
        'X-EBAY-API-SITEID: 15',
        'X-EBAY-API-CALL-NAME: GetMemberMessages'
    );
    return request($theData, $headers, $Credentials['url']);
}

function save_to_db($memberMessageData, $con){
    $index = 0;
    foreach ($memberMessageData->MemberMessage->MemberMessageExchange as $message){
        $index++;
        $username = $message->Item->Seller->UserID;
        $displayname = $message->Item->ConditionDisplayName;
        $message_date = $message->CreationDate;
        $message_status = $message->MessageStatus;
        $source = "eBay";
        $message_content = $message->Question->Body;
        $item_title = $message->Item->Title;
        $item_url = $message->Item->ListingDetails->ViewItemURL;
        $message_id = $message->Question->MessageID;
        $message_type = $message->Question->MessageType;
        $item_id = $message->Item->ItemID;
        $recipient_id = $message->Question->RecipientID;

        $query = "SELECT * from messages_api where message_id ='$message_id'";
        $result = mysqli_query($con, $query);
        if (mysqli_num_rows($result)>0) ;
        else {
              $query = "INSERT INTO messages_api (username, displayname, message_date,message_type, message_status, source, message, item_title, item_url,message_id , item_id , recipient_id)
                         VALUES ('".$username."', '".$displayname."', '".$message_date."', '".$message_type."', '".$message_status."', '".$source."', '".$message_content."', '".$item_title."', '".$item_url."', '".$message_id."', '".$item_id."', '".$recipient_id."')";
              if ($con->query($query) === TRUE) {
                  echo "New record created successfully";
              } else {
                   echo "Error: " . $query . "<br>" . $con->error;
              }    
        }
    }
}

function main($Credentials, $con){
    $startTime = new DateTime('2018-04-08T00:00:00.000Z');
    $StartCreationTime = $startTime->format('c');
    $endTime = new DateTime('now');
    $EndCreationTime = $endTime->format('c');
    $memberMessageData = getMemberMessages($Credentials, $StartCreationTime, $EndCreationTime,1);

    // save message data to db
    $totalPageNumber = $memberMessageData->PaginationResult->TotalNumberOfPages;
    if ($totalPageNumber > 0){
        save_to_db($memberMessageData, $con);
        for ($i = 1; $i <= $totalPageNumber ; $i++){
            $memberMessageData = getMemberMessages($Credentials, $StartCreationTime, $EndCreationTime, $i);        
            echo "<pre>";
                print_r($memberMessageData);
            echo "</pre>";
            save_to_db($memberMessageData, $con);
        }    
    }
    $EndCreationTime = $StartCreationTime;

    /**Send Reply to Unreaded Message from DB */
    send_message_from_db($con);
}


/*
    Send reply to unreaded messages of database
    
    @variables
    $itemID       :  item_id from db
    $message_body :  message content
    $message_id   :  message id received from buyer , can get from db
    $recipentID   :  recipentID from db

    @functions
    send_message  : message sending function
    send_message_from_db : get message data from db and call send_message function
*/

function send_message($Credentials, $itemID, $message_body, $message_id, $recipentID){
    echo $itemID."<br/>";
    echo $message_id."<br/>";
    echo $recipentID."<br/>";
    $theData = '<?xml version="1.0" encoding="utf-8"?>
                <AddMemberMessageRTQRequest xmlns="urn:ebay:apis:eBLBaseComponents">
                <RequesterCredentials>
                    <eBayAuthToken>'.$Credentials['authToken'].'</eBayAuthToken>
                </RequesterCredentials>
                <ItemID>'.$itemID.'</ItemID>
                <MemberMessage>
                    <Body>'.
                    $message_body
                   .'</Body>
                    <DisplayToPublic>true</DisplayToPublic>
                    <EmailCopyToSender>true</EmailCopyToSender>
                    <ParentMessageID>'.$message_id.'</ParentMessageID>'.
                    // <RecipientID>brennamcintyre</RecipientID>
                '</MemberMessage>
                </AddMemberMessageRTQRequest>';

    $headers = array(
                'Content-Type: text/xml',
                'X-EBAY-API-COMPATIBILITY-LEVEL: 1047',
                'X-EBAY-API-DEV-NAME:'.$Credentials['devId'],
                'X-EBAY-API-APP-NAME:'.$Credentials['appId'],
                'X-EBAY-API-CERT-NAME:'.$Credentials['certId'],
                'X-EBAY-API-SITEID: 15',
                'X-EBAY-API-CALL-NAME: AddMemberMessageRTQ'
               );
    $res = request($theData, $headers, $Credentials['url']);
    echo "<pre>";
    print_r($res);
    echo "</pre>";    
}
function send_message_from_db($Credentials, $con){
    
    $query = "SELECT * from messages_api where message_status='Unanswered'";
    $result = mysqli_query($con, $query);
    if (mysqli_num_rows($result)>0){
        $index = 0;
        while ($row = $result->fetch_assoc()) {
            $index++;
            $itemID = $row['item_id'];
            $message_body = "This is Answer".$index;
            $message_id = $row['message_id'];
            $recipentID = $row['recipient_id'];
            send_message($Credentials, $itemID, $message_body, $message_id, $recipentID);
            $query = "UPDATE messages_api SET message_status='Answered' where message_id='".$message_id."'";
            if ($con->query($query) === TRUE) {
                echo "New record Updated successfully";
            } else {
                 echo "Error: " . $query . "<br>" . $con->error;
            }
        }        
    } 
}


main($Credentials, $con);



