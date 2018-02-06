<?php

/****************************************************
 *
 * PHP Getting Started Example for ELiT API
 * To receive free trial API credentials please
 * contact ELiT. 
 *
 * This example will otherwise work as is, using a static
 * JSON file to display example text. That is, you will
 * still be able to see example JSON format returned from the 
 * API and an example of how to display these results without
 * API credentials.
 *
 /****************************************************
 
 
 /****************************************************
 * 
 * Configuration settings
 *
 ***************************************************/

// Store submitted input text in session:
session_start();

// ElíT API URL
$api_url = "https://api-staging.englishlanguageitutoring.com";

// ElíT API account ID
$account_id = getenv('WI_ACCOUNT_ID');

// ElíT API account secret token
$account_token = getenv('WI_ACCOUNT_TOKEN');

// Use the static example (API info not set above)
$static = true;

// The question to display on the webpage
$question_text = "Write a letter to organise a surprise birthday party";

// Colour for low quality sentences
$color_sentence_low = "#ffbc99";

// Colour for medium quality sentences
$color_sentence_med = "#ffee99";

// Colour for high quality sentences
$color_sentence_high = "#ffffff";

// Colour for the box around suspect tokens
$color_token_suspect = "#d24a00";

// Colour for the box around error tokens
$color_token_error = "#d24aff";

// Score threshold for high quality sentences
$sentence_threshold_high = 0.33;

// Score threshold for low quality sentences
$sentence_threshold_low = -0.33;

// Display the detailed response from the API for development purposes
$print_api_response = True;

/****************************************************
 *
 * If the API credentials are not set then use
 * the static example to illustrate how to display
 * the JSON returned from the API.
 *
 ***************************************************/


if(!isset($account_id) || $account_id == '' || !isset($account_token) || $account_token == '') {
	$static = true;
} else {
	$static = false;
}

/****************************************************
 *
 * Helper functions for interacting the API
 *
 ***************************************************/


/**
 * The general function for interacting with the API
 */
function CallAPI($method, $url, $headers = array(), $data = false, $static){
	if($static) {
		// overwrite API call to grab the static json file that matches the default
		// non-editable text in the form:
		$method = "GET";
		$url = "https://s3-eu-west-1.amazonaws.com/elit-website-media/results-example-api.json"; 
		$headers = array();
		$data = false;
	} 
	
    $curl = curl_init();
    switch ($method)
    {
        case "POST":
            curl_setopt($curl, CURLOPT_POST, 1);
            if ($data)
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            break;
        case "PUT":
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
            if ($data)
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            break;
        default:
            if ($data)
                $url = sprintf("%s?%s", $url, http_build_query($data));
    }

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($curl);
    curl_close($curl);
    return $result;
}

/**
 * Function for submitting text to the API
 */
function submitTextToAPI($author_id, $task_id, $session_id, $question_text, $text_id, $text, $test, $static){
    global $account_id, $account_token, $api_url;
    $data = array(
      "author_id" => $author_id,
      "task_id" => $task_id,
      "session_id" => $session_id,
      "question_text" => $question_text,
      "text" => $text,
      "test" => $test
    );
    $data = json_encode($data);
    echo $$api_url;
    $headers = array("Authorization: Token token=".$account_token, "Content-Type: application/json");
    $result = CallAPI("PUT", $api_url."/v2.0.0/account/".$account_id."/text/".$text_id, $headers, $data, $static);
    return $result;
}

/**
 * Function for getting the results for a submitted text from the API
 */
function getResults($text_id, $static){
    global $account_id, $account_token, $api_url;
    $headers = array("Authorization: Token token=".$account_token);
    $result = CallAPI("GET", $api_url."/v2.0.0/account/".$account_id."/text/".$text_id."/results", $headers, false, $static);
    return $result;
}

/**
 * Function for generating a random string to use as an identifier
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

/****************************************************
 *
 * Preparing the data for display
 *
 ***************************************************/

// Generating a random user identifier
$user_id = isset($_GET["id"])?$_GET["id"]:generateRandomString(20); 
$page = "main"; //The default view of the website
$debug_output = "";

/**
 * If a text was just submitted, send it to the API to be graded.
 */
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["input_text"]) && !empty($_POST["input_text"]) && isset($_GET["id"]) && !isset($_POST["hidden"])) {
    $_POST["input_text"] = preg_replace('/\\n/', '<br>', $_POST["input_text"]);
    $_POST["input_text"] = preg_replace('/\s+/', ' ', $_POST["input_text"]);
    submitTextToAPI("APIDemoExampleAuthor", "APIDemoExampleTask", $_GET["id"], $question_text, $_GET["id"], $_POST["input_text"], 1, $static);
	
	// save text in session:
	$_SESSION['input_text'] = $_POST["input_text"];
	$delay=2; //Where 0 is an example of time Delay you can use 5 for 5 seconds for example !
	header("Refresh: $delay;");
	//echo 'You\'ll be redirected in about 2 secs. ';
	//echo "saved in session now";
}

/**
 * If a text has been submitted (the user has an active ID), 
 * keep checking the API for results.
 * If the results are not yet ready, show the loader page.
 * Otherwise, prepare the text by applying the colours to 
 * sentences and putting boxes around tokens.
 */
if (isset($_GET["id"])) {
    $results = getResults($_GET["id"], $static);
    $results = json_decode($results);
    $debug_output .= print_r($results, true);
    if(strcmp($results->{"type"}, "results_not_ready") == 0){
        $page = "loader";
        $delay=2; //Where 0 is an example of time Delay you can use 5 for 5 seconds for example !
		header("Refresh: $delay;"); 
    }
    else if($results->{"type"} == "success"){
    	//$delay=5000; //Where 0 is an example of time Delay you can use 5 for 5 seconds for example !
		//header("Refresh: $delay;"); 
		
		$fp = fopen('results.json', 'w');
		fwrite($fp, json_encode($results));
		fclose($fp);
		
        $page = "results";
        $tags = array();
        foreach($results->{"sentence_scores"} as $sentence_scores){
            if($sentence_scores[2] < $sentence_threshold_high && $sentence_scores[2] > $sentence_threshold_low){
                $sentence_color = $color_sentence_med;
            }
            else if($sentence_scores[2] < $sentence_threshold_low){
                $sentence_color = $color_sentence_low;
            }
            else{
                $sentence_color = $color_sentence_high;
            }
            $tags[$sentence_scores[0]] = "<span style=\"background-color:".$sentence_color."\" data-sentence-score=\"".floatval($sentence_scores[2])."\">";
            $tags[$sentence_scores[1]] = "</span>";
        }

        foreach($results->{"suspect_tokens"} as $suspect_tokens){
            $tags[$suspect_tokens[0]] = "<span style=\"border:2px solid ".$color_token_suspect.";\">";
            $tags[$suspect_tokens[1]] = "</span>";
        }

        foreach($results->{"textual_errors"} as $textual_errors){
            $tags[$textual_errors[0]] = "<span style=\"border:2px solid ".$color_token_error.";\">";
            $tags[$textual_errors[1]] = "</span>";
        }


		// get text submitted from session:
        $submitted_text = $_SESSION['input_text']; //$_POST["input_text"];
        $submitted_text_chars = preg_split('//u', $submitted_text, -1, PREG_SPLIT_NO_EMPTY);
        $processed_text = "";
        for($i=0; $i <= count($submitted_text_chars); $i++){
            if(array_key_exists($i, $tags)){
                $processed_text .= $tags[$i];
            }
            if($i < count($submitted_text_chars))
                $processed_text .= htmlspecialchars($submitted_text_chars[$i]);
        }
        
        $processed_text = preg_replace('/\&lt\;br\&gt\;/', '<br>', $processed_text);
    }
} else{ 
	// no text submitted - new input expected - reset session variables:
	$_SESSION['input_text'] = "";
}

/****************************************************
 *
 * Displaying the webpage
 *
 ***************************************************/

?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="style.css" />
    
    <title>ELiT API example</title>
    </head>
    <body>


<?php 

/**
 * The main page, shown when the user first arrives.
 */
if($page == "main") { 
?>
    <div class="container" id="page-input">
    
    	<?php if($static) {
			echo "<p>API credentials unavailable - please set to use live API.</p>
				<p>Text is not editable. This example will not connect to the API but instead use example JSON file returned from the API. Please contact ELiT to apply for free trial API access.</p>";
		} ?>
    
        <h3><?php print htmlspecialchars($question_text); ?></h3>
        <form method="post" action="<?php print("?id=".$user_id); ?>">
            <div class="form-group">
                <?php  if(isset($static) && $static) { ?>
                <textarea id="input_text" class="form-control" 
                	name="input_text" readonly>Dear Mrs Brown, 

I am writing in connection with a surprise birthday party for your husband, Mr. Brown. We are writing to invite you and to give you some information about the party. All our class love Mr Brown very much, so we decided to organise a surprise party for him. The party in on Tuesday 16 of June. You should come on 3 pm in college Canteen . We have bought some snaks to eat and three students will sing for him, also . Besides this, we have invited all other teachers and the Principal of our school. Of course all the class will take party to this party. Furthermore , we don't know what present buying for him. So we would appreciate if you help us with this matter. We have thought to buy a cd or a book. He loves to read books. What do you believe ? If he needs something else, we are happy to buy this. I am looking forward to hearing from you soon especially as I am concerned about this matter. 

Yours sincerely,

John Smith
				<?php  
					} else { ?>
                	<textarea id="input_text" class="form-control" name="input_text"><?php if(isset($_SESSION['input_text'])) {
                		print(htmlspecialchars($_SESSION['input_text'])); 
                	}
                }
            ?></textarea>
            </div>
            <button type="submit" class="btn btn-default" id="submit">Submit</button>
        </form>
    </div>
<?php 
} 

/**
 * The loader page, shown while the text is being graded
 */
else if($page == "loader") { 

?>
    <div class="container" id="page-loader">
        <div class="loader"></div>
    </div>
    <?php
    	$delay=3; //Where 0 is an example of time Delay you can use 5 for 5 seconds for example !
		header("Refresh: $delay;"); 
    ?>
<?php 
} 

/**
 * The results page, showing the output from the API
 */
else if($page == "results" && isset($results)) {
?>
    <div class="container" id="page-output">
        <div id="output">
            <h3><?php print htmlspecialchars($question_text); ?></h3>
            <div class="overall_score"><strong>Overall score:</strong> <?php print($results->{"overall_score"}); ?></div>
            <div id="analysis" style="line-height:160%;"><?php print($processed_text);?></div>
        </div>
        <form method="post" action="?">
            <textarea id="input_text" class="form-control hidden" name="input_text">
            <?php if(isset($_SESSION['input_text'])) print(htmlspecialchars($_SESSION['input_text'])); ?></textarea>
            <button type="submit" class="btn btn-default" id="submit">Try again</button>
        </form>
    </div>
<?php 
} 

/**
 * Printing the detailed output from the API
 */
if($print_api_response == True && strlen($debug_output) > 0) 
{
?>
    <div class="container">
        <div id="page-debug">
            <pre>
            <?php 
            	print(htmlspecialchars($debug_output)); 
            ?>
            </pre>
        </div>
    </div>
<?php
}
?>
  </body>
</html>
