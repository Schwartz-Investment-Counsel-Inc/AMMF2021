<?php

  require_once 'mysql.obj.inc.php';
  require_once 'library.php';
  require_once 'authentication.inc.php';
  require_once 'fix_mysql.inc.php';

  $mysql = new mysql();
  $mysql->connectDB ();

  // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  // Verifiers
  // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

  function verifyRecaptchaResponse($recaptcha_response) {

    $recaptcha_url = "https://www.google.com/recaptcha/api/siteverify";
    $recaptcha_secret = "6LffrwoTAAAAACzRU3EC0FpLJx95pm-09wmNbTSw";
    $remoteip = $_SERVER['REMOTE_ADDR'];

    $fields = array(
      'secret' => $recaptcha_secret,
      'response' => $recaptcha_response,
      'remoteip' => $remoteip
    );

    $ch = curl_init($recaptcha_url);

    curl_setopt($ch, CURLOPT_URL, $recaptcha_url);
    curl_setopt($ch, CURLOPT_POST, count($fields));
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $recaptcha_res = curl_exec($ch);
    curl_close($ch);

    $verified = json_decode($recaptcha_res)->{'success'};

    if (!$verified) {
      sleep(3); // penalty
      send422('There was a problem while verifying your submission. Please try again.');
      return false;
    }

    return true;
  }

  /**
   * Combines required params check w/ validations. Returns false if an issue was found.
   * Validation is a map of error message to boolean. If boolean is true, then there is a validation failure.
   */
  function verifyParamsAndValidations($requiredParams, $validatons) {
    // Required params
    if (!verifyRequiredParams($requiredParams)) return false;

    // Validations
    foreach ($validatons as $key => $value) {
      if ($value) {
        send422($key);
        return false;
      }
    }

    return true;
  }

  /**
   * Verifies all required parameters present.
   */
  function verifyRequiredParams($requiredParams) {

    $missing = array();
    foreach ($requiredParams as $key => $value) {
      if (!isset($value)) {
        array_push($missing, $key);
      }
    }

    if (sizeof($missing) > 0) {
      $msg = "Missing the following: ".join(", ", $missing);
      send422($msg);
      return false;
    }

    return true;

  } // verifyRequiredParams

  /**
   * Verifies passwords match.
   */
  function verifyMatchingPasswords($password1, $password2) {

    if (strcmp($password1, $password2) != 0) {
      send422("Passwords do not match");
      return false;
    }

    return true;
  }


  // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  // Utilities
  // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

  /**
   * Request type methods
   */
  function isGetRequest() { return isRequest('GET'); }
  function isPostRequest() { return isRequest('POST'); }
  function isPutRequest() { return isRequest('PUT'); }
  function isDeleteRequest() { return isRequest('DELETE'); }
  function isRequest($type) { return $type === $_SERVER['REQUEST_METHOD']; }

  /**
   * Response helper methods
   */
  function sendAsJson($obj) {
    header('Content-Type: application/json');
    echo json_encode($obj);
  }
  function send404($msg) { sendResponse("HTTP/1.1 404 Not Found", $msg); }
  function send422($msg) { sendResponse("HTTP/1.1 422 Unprocessable Entity", $msg); }
  function sendResponse($header, $msg) {
    header($header);
    if ($msg) {
      echo $msg;
    }
  }

  /**
   * Parses out request body.
   */
  function requestBody($default = null) {
    if ($default == null) $default = new AnObject(); // bah
    $payload = json_decode(rawRequestBody());
    return $payload ? $payload : $default;
  }

  function rawRequestBody() {
    return file_get_contents('php://input');
  }

  /**
   * Ad hoc workaround for lack of optionals....
   */
  function obj_property($obj, $prop, $default=NULL) {
    if (isset($obj->{$prop})) {
      return $obj->{$prop};
    } else {
      return $default;
    }
  }

  // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  // Constructor helpers
  // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

  /**
   * Returns populated FundSummary for fund.
   */
  function summary_for_fund($symbol) {

    global $mysql;

    $priceSectionPrice        = get_current_price_str($mysql, $symbol);
    $priceSectionPriceChange  = get_daily_change_in_dollars_str($mysql, $symbol);
    $priceSetionPercentChange = get_daily_change_in_percentage_str($mysql, $symbol);

    return FundSummary($symbol, $priceSectionPrice, $priceSectionPriceChange, $priceSetionPercentChange);
  }

  /**
   * Returns populated FundDetails for fund.
   */
  function details_for_fund($symbol) {

    global $mysql;

    // - - - - - - - - - -
    $priceSectionPrice        = get_current_price_str($mysql, $symbol);
    $priceSectionPriceChange  = get_daily_change_in_dollars_str($mysql, $symbol);
    $priceSetionPercentChange = get_daily_change_in_percentage_str($mysql, $symbol);
    $priceSectionUpdated      = get_last_trade_date($mysql, $symbol);

    // - - - - - - - - - -
    $total_returns = get_dynamic_table($mysql, $symbol, 'total_returns');

    $totalReturnsSectionColumns = $total_returns->headers;
    $totalReturnsSectionRows    = $total_returns->rows;
    $totalReturnsSectionFooter  = get_total_returns_footer($mysql, $symbol);

    // - - - - - - - - - -
    $fundInfoSectionInfo = array();

    {
      $query = "SELECT * FROM ${symbol}_fund_information;";
      $result = $mysql->getResults ($query);
      for ($i = 0; $i < sizeof($result); $i++) {
          $row = $result[$i];
          array_push($fundInfoSectionInfo, NameValuePair($row[1], $row[2]));
      }
    }

    return FundDetails($symbol,
                       $priceSectionPrice,
                       $priceSectionPriceChange,
                       $priceSetionPercentChange,
                       $priceSectionUpdated,
                       $totalReturnsSectionColumns,
                       $totalReturnsSectionRows,
                       $totalReturnsSectionFooter,
                       $total_returns->annualized_flags,
                       $fundInfoSectionInfo);

  } // details_for_fund

  // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  // Constructors
  // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  /**
   * Fund summary constructor. (Used on fund family page.)
   */
  function FundSummary($symbol, $price, $priceChange, $percentChange) {
    $fund = new AnObject();
    $fund->symbol = $symbol;
    $fund->price = $price;
    $fund->priceChange = $priceChange;
    $fund->percentChange = $percentChange;
    return $fund;
  }

  /**
   * Generic name-value pair
   */
  function NameValuePair($name, $value) {
    $pair = new AnObject();
    $pair->name = $name;
    $pair->value = $value;
    return $pair;
  }

  /**
   * Error Response
   */
  function ErrorResponse($msg) {
    $res = new AnObject();
    $res->error = $msg;
    return $res;
  }

  /**
   * Fund details constructor. (Used on individual fund page.)
   */
  function FundDetails($symbol,
                       // - - - - - - - - - -
                       $priceSectionPrice,
                       $priceSectionPriceChange,
                       $priceSetionPercentChange,
                       $priceSectionUpdated,
                       // - - - - - - - - - -
                       $totalReturnsSectionColumns,
                       $totalReturnsSectionRows,
                       $totalReturnsSectionFooter,
                       $totalReturnsAnnualized,
                       // - - - - - - - - - -
                       $fundInfoSectionInfo) {

    $fund = new AnObject();
    $fund->symbol = $symbol;

    // priceSection
    $fund->priceSection = new AnObject();
    $fund->priceSection->visible        = true; // TODO: remove this
    $fund->priceSection->price          = $priceSectionPrice;
    $fund->priceSection->priceChange    = $priceSectionPriceChange;
    $fund->priceSection->percentChange  = $priceSetionPercentChange;
    $fund->priceSection->updated        = $priceSectionUpdated;

    // totalReturnsSection
    $fund->totalReturnsSection = new AnObject();
    $fund->totalReturnsSection->visible = true; // TODO: remove this
    $fund->totalReturnsSection->columns = $totalReturnsSectionColumns;
    $fund->totalReturnsSection->rows    = $totalReturnsSectionRows;
    $fund->totalReturnsSection->footer  = $totalReturnsSectionFooter;
    $fund->totalReturnsSection->annualized = $totalReturnsAnnualized;

    // fundInfoSection
    $fund->fundInfoSection = new AnObject();
    $fund->fundInfoSection->visible = true; // TODO: remove this
    $fund->fundInfoSection->info    = $fundInfoSectionInfo;

    return $fund;

  } // FundDetails

  /**
   * Generic class for creating objects with generic getter/setters.
   * Source: http://php.net/manual/en/language.types.object.php#114442
   */
  class AnObject {
    public function __construct(array $arguments = array()) {
        if (!empty($arguments)) {
            foreach ($arguments as $property => $argument) {
                $this->{$property} = $argument;
            }
        }
    }

    public function __call($method, $arguments) {
        $arguments = array_merge(array("stdObject" => $this), $arguments); // Note: method argument 0 will always referred to the main class ($this).
        if (isset($this->{$method}) && is_callable($this->{$method})) {
            return call_user_func_array($this->{$method}, $arguments);
        } else {
            // throw new Exception("Fatal error: Call to undefined method stdObject::{$method}()");

            // Customization: return null if not defined
            return NULL;
        }
    }
  }

?>
