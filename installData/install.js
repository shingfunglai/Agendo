path = '';
dbName = '';
makeDB = false;

function back(){
	document.getElementById('htConnectTable').style.display='table'
	document.getElementById('databaseData').style.display='none'
}

function postMe(functionName){
	data = [];
	if(functionName == 'makeHtConnect'){
		data = getHtConnectData();
		document.getElementById('htConnectTable').style.display='none'
		document.getElementById('databaseData').style.display='table'
	}
	else if(functionName == 'applySql'){
		data = getDBData();
		document.getElementById('successError').innerHTML = "Importing data from the script, please wait.";
		document.getElementById('applySql').disabled = true;
		document.getElementById('back').disabled = true;
	}
	
	$.post('install.php', {functionName: functionName, 'data[]': data},function(message){	if(functionName == 'makeHtConnect'){
																								message = message.split('\n');
																								if(message[0] == 'false') document.getElementById('applySql').disabled = true;
																								message = message[1];
																							}
																							else {
																								document.getElementById('applySql').disabled = false;
																								document.getElementById('back').disabled = false;
																							}
																							document.getElementById('successError').innerHTML = message;
																						});
}

function getHtConnectData(){
	dbEngine = 	document.getElementById('dbEngine').value;
	dbName = 	document.getElementById('dbName').value;
	dbUser = 	document.getElementById('dbUser').value;
	dbPass = 	document.getElementById('dbPass').value;
	path =	 	document.getElementById('path').value;
	
	makeDB = 	document.getElementById('makeDB').checked;
	return [dbEngine, dbName, dbUser, dbPass, path];
}

function getDBData(){
	adminId = 		document.getElementById('adminId').value;
	adminPass = 	document.getElementById('adminPass').value;
	adminFirst = 	document.getElementById('adminFirst').value;
	adminLast = 	document.getElementById('adminLast').value;
	adminPhone = 	document.getElementById('adminPhone').value;
	adminExt = 		document.getElementById('adminExt').value;
	adminMobile = 	document.getElementById('adminMobile').value;
	adminMail = 	document.getElementById('adminMail').value;

	institute = 		document.getElementById('institute').value;
	instituteShort = 	document.getElementById('instituteShort').value;
	instituteUrl = 		document.getElementById('instituteUrl').value;
	instituteMail = 	document.getElementById('instituteMail').value;
	institutePass = 	document.getElementById('institutePass').value;
	instituteHost = 	document.getElementById('instituteHost').value;
	institutePort = 	document.getElementById('institutePort').value;
	
	applySql = 		document.getElementById('applySql').value;
	
	return [adminId, adminPass, adminFirst, adminLast, adminPhone, adminExt, adminMobile, adminMail,
	        institute, instituteShort, instituteUrl, instituteMail, institutePass, instituteHost, institutePort,
	        applySql, makeDB, dbName, path];
}