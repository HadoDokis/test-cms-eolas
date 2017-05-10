formCtrl.schemes = [["isNotNull","\"%s\" field must be filled."],
						["isDate","\"%s\" field does not contain a valid date.\nFormat : mm/dd/yyyy."],
						["isEmail","\"%s\" field does not contain a valid email address."],
						["isInt","\"%s\" field does not contain an integer."],
						["isFloat","\"%s\" field does not contain a float."],
						["min","The selection named "%s" must have at least %d items."], 
						["max","The selection named "%s" must have at most %d items."]
					];
					
formCtrl.fx.isDate = function(s) {
  var bIsDate, d, m, y;
	bIsDate = true;
	if (this.isNotNull(s)) {
		if ((s.length != 10) || (s.substring(2,3) != "/") || (s.substring(5,6) != "/")) bIsDate = false;
		var m = s.substring(0,2);
		var d = s.substring(3,5);
		var y = s.substring(6,10);	
		if (m==1 || m==3 || m==5 || m==7 | m==8 || m==10 || m==12) {
			if (d > 31) bIsDate = false;
		} else if (m==4 || m==6 || m==9 || m==11) {
			if (d > 30) bIsDate = false;	
		} else if (m==2) {
			if (y % 4 == 0) {
				if (d > 29) bIsDate = false;	
			} else {
				if (d > 28) bIsDate = false;	
			}
		} else {
			bIsDate = false;	
		}
	}
	return bIsDate;

}