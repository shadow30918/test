(function (targetWidth) {
	var deviceWidth = screen.width;
	//if (deviceWidth == 414) deviceWidth = targetWidth;
	var ratio = deviceWidth / targetWidth;
	//if (ratio > 1.2) ratio = 1.2;
	//if (ratio > 1) ratio = 1;
	//if (ratio > 1.4) ratio = 1.4;
	var viewport = document.querySelector('meta[name="viewport"]');
	if (ratio > 1.4)
		viewport.setAttribute('content', 'width=device-width, initial-scale=1.0, user-scalable=yes');
	else if(ratio > 1)
		viewport.setAttribute('content', 'width=' + targetWidth + ', initial-scale=' + ratio + ', minimum-scale=' + ratio + ', maximum-scale=' + ratio + ', user-scalable=yes');
	else
		viewport.setAttribute('content', 'width=device-width, initial-scale=' + ratio + ', minimum-scale=' + ratio + ', maximum-scale=' + ratio + ', user-scalable=yes');
})(1200);
