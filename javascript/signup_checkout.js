var mainForm;

var couponMaskAppliesToDomainNameRegistration = 1;
var couponMaskAppliesToDomainNameTransfer = 2;
var couponMaskAppliesToPackage = 4;
var couponMaskAppliesToPackageSetup = 8;
var couponMaskAppliesToAddons = 16;
var couponMaskAppliesToAddonsSetup = 32;
var couponMaskAppliesToOther = 64;

// State vars
var validateCouponCount = false;
var couponArr = new Array();
var taxRate = 0;
var taxRateDisp = 0;
var useVAT = 0;
var validVATNumber = 0;
var taxName = '';
var tax2Rate = 0;
var tax2RateDisp = 0;
var useVAT2 = 0;
var tax2Name = '';
var isTax2Compound = 0;

/* Stock Count Down */
var sc_days = 0;var sc_hours = 0;var sc_seconds = 0;var sc_minutes = 0;
function setStockCountDown ()
{
  sc_seconds--;
  if (sc_seconds < 0){
      sc_minutes--;
      sc_seconds = 59
  }
  if (sc_minutes < 0){
      sc_hours--;
      sc_minutes = 59
  }
  if (sc_hours < 0){
      sc_days--;
      sc_hours = 23
  }

  if (sc_minutes < 1) {
	  $("#remain").html("<span style='color:red;font-weight:bold;'>"+sc_minutes+" minutes, "+sc_seconds+" seconds</span>");
  } else {
	  $("#remain").html(sc_minutes+" minutes, "+sc_seconds+" seconds");  
  }
  SD=window.setTimeout( "setStockCountDown()", 1000 ); 
  if (sc_minutes == '00' && sc_seconds == '00') { 
  		sc_seconds = "00"; 
  		window.clearTimeout(SD);
  		alert(lang('Your order has elapsed.'));
		window.location = "order.php?cleanCart=true";
  }
}