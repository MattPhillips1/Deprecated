function fillLargeCanvas(userID){

	$('#body_content').html("<div id=\"map\" style=\"display: inline-block;\">\n" + 
			"<canvas class=\"full_screen_map\" id=\"myCanvas\"></canvas>" +
			"</div>");
	canvas = document.getElementById('myCanvas');
	context = canvas.getContext('2d');
	canvas.width = $(window).width() - 200;
	canvas.height = $(window).height() - 168;
	imageObj = new Image();

	offScreen = document.createElement('canvas');
	osContext = offScreen.getContext('2d');

	imageObj.onload = function() {

		i = 1;
		offScreen.width = imageObj.width * 0.5;
		offScreen.height = imageObj.height * 0.5;
		osContext.drawImage(imageObj, 0, 0, offScreen.width, offScreen.height);
		while (i < 0){

			osContext.drawImage(offScreen, 0, 0, offScreen.width * 0.5, offScreen.height * 0.5);
			i += 1;
		}
	//context.drawImage(offScreen, 0, 0, offScreen.width, offScreen.height);

	context.drawImage(imageObj, 0, 0, canvas.width, canvas.height);
	console.log(context.getImageData(800, 300, 1,1).data);
	getPixelsAndFill(userID, canvas, context);
	
	};
	imageObj.src = "images/stock/Simple_world_map.png";
}

// Algorithm for filling in a country in the map. 
// Currently a bit slow. 
// TODO: improve performance (maybe by doing squares?)
function fillCountries(canvas, context, pixelStack){
	//newdata = context.createImageData(1,1);
	offScreen = document.createElement('canvas');
	osContext = offScreen.getContext('2d');
	newdata = osContext.createImageData(1,1);
	newdata.data[0] = 4;
	newdata.data[1] = 128;
	newdata.data[2] = 80;
	newdata.data[3] = 255;
	red = 4;
	green = 128;
	blue = 80;
	imageData = context.getImageData(pixelStack[0][0], pixelStack[0][1], 1, 1).data;
	while(pixelStack.length){
		cood = pixelStack.pop();
		//console.log(pixelStack.length);
		y = cood[1];
		
		x = cood[0];
		while (y >= 0 && sameColour(x, y, imageData, context)){
			y--;
		}
		//console.log("found boundary");
		//console.log(y);
		//console.log(x);
		yInit = ++y;
		height = 0;
		//console.log(y);
		addedLeft = false;
		addedRight = false;

		while (y <= canvas.height && sameColour(x, y, imageData, context)){
			//context.putImageData(newdata, x, y++);
			++y;
			height += 1;
			if (!addedRight){
				if (sameColour(x+1, y, imageData, context)){
					pixelStack.push([x+1, y]);
					addedRight = true;
				}
			}else if(!sameColour(x+1, y, imageData, context)){
				addedRight = false;
			}
			if (!addedLeft){
				if (sameColour(x-1, y, imageData, context)){
					pixelStack.push([x-1, y]);
					addedLeft = true;
				}
			}else if(!sameColour(x-1, y, imageData, context)){
				addedLeft = false;
			}

			
		}
		
		newdata = osContext.createImageData(1,height);
		for (i = 0; i < newdata.data.length; ++i){
			switch(i%4){
				case 0:
					newdata.data[i] = 4;
					break;
				case 1:
					newdata.data[i] = 128;
					break;
				case 2:
					newdata.data[i] = 80;
					break;
				case 3:
					newdata.data[i] = 255;
			}
		}
		//context.putImageData(newdata, x, y++)
		context.putImageData(newdata, x, yInit);

		
	}
}

// Check if a pixel is the same colour as another next to it
function sameColour(X, Y, initData, context){

	checkImageData = context.getImageData(X, Y, 1, 1).data;
	//console.log("compare to ");
	//console.log(initData);
	newR = checkImageData[0];
	newG = checkImageData[1];
	newB = checkImageData[2];

	//console.log(newR);
	//console.log(newG);
	//console.log(newB);


	return(newR == initData[0] && newG == initData[1] && newB == initData[2]);
}

function dropPins(leftPercent, topPercent){
	
	canvas = $('#myCanvas');
	console.log(canvas.offset());
	left = canvas.width() * leftPercent/100;
	topOffset = canvas.height() * topPercent/100;
	console.log(topOffset);
	console.log(left - canvas.offset()['left']);
	left = left + canvas.offset()['left'] - 20;
	topOffset = topOffset + canvas.offset()['top'] - 50;
	style = "style=\"position: absolute; left: " + left +"px; top: " + topOffset + "px; z-index: 2;\">";
	$("#map").append("<img src=\"images/stock/pin.png\" height=\"50\" width=\"40\" " + style);
	
}

// Gets the percentage coordinates of where to fill in
// Is an AJAX call so that all of the maps will fill in at roughly the same time
// Should it fill in one at a time?
// Probably good so that it can request and wait for the data as it fills in other maps?
function getPixelsAndFill(userID, canvas, context){
	postVars = {'coodRequest': 'true', 'forUser': userID};
	makeAJAX(postVars).done(function(response){
		pixelStack = convertPercentage(response, canvas);

		fillCountries(canvas, context, pixelStack);
	});
}

function convertPercentage(percentages, canvas){
	pixelStack = [];
	for (set in percentages){
		xPixel = percentages[set]['x']/100*canvas.width;
		yPixel = percentages[set]['y']/100*canvas.height;
		pixelStack.push([xPixel, yPixel]);
		dropPins(percentages[set]['x'], percentages[set]['y']);

	}
	return pixelStack;
}
/*

			function fillCountriesRecursive(canvas, context){

			pixelStack = [[1000, 150]];
			imageData = context.getImageData(pixelStack[0][0], pixelStack[0][1], 1, 1).data;
			y = pixelStack[0][1];
			x = pixelStack[0][0];

			while (pixelStack.length){
				cood = pixelStack.pop();
				y = cood[1];
				x = cood[0];

				addedTop = false;
				addedBottom = false;
				addedLeft = false;
				addedRight = false;

				while (y >= 0 && sameColour(x, y, imageData, context)){
					--y;
				}
				++y;
				highest = y;
				
				/*
				startY = y;
				while (y <= canvas.height && sameColour(x, y, imageData, context)){
					++y;
				}
				--y;
				height = y - startY;
				area = height;
				maxArea = area;
				
				while (x >= 0 && sameColour(x, y, imageData, context)){
					--x;
					if (!addedTop){
						if (sameColour(x, y-1, imageData, context)){
							pixelStack.push([x, y-1]);
							addedTop = true;
						}
					}else if(!sameColour(x, y-1, imageData, context)){
							addedTop = false;
					}

				}
				++x;
				farLeft = x;
				x = cood[0]
				addedTop = false;
				while (x <= canvas.width && sameColour(x, y, imageData, context)){
					++x;
					if (!addedTop){
						if (sameColour(x, y-1, imageData, context)){
							pixelStack.push([x, y-1]);
							addedTop = true;
						}
					}else if(!sameColour(x, y-1, imageData, context)){
						addedTop = false;
					}
				}
				--x;
				farRight = x;
				x = cood[0];

				while (y <= canvas.height && sameColour(x, y, imageData, context)){
					++y;
				}
				--y;
				lowest = y;

				while (x >= 0 && sameColour(x, y, imageData, context)){
					--x;
					if (!addedBottom){
						if (sameColour(x, y+1, imageData, context)){
							pixelStack.push([x, y+1]);
							addedBottom = true;
						}
					}else if(!sameColour(x, y+1, imageData, context)){
							addedBottom = false;
					}
				}
				++x;
				if (x > farLeft){
					farLeft = x;
				}
				addedBottom = false;
				x = cood[0];
				while (x <= canvas.width && sameColour(x, y, imageData, context)){
					++x;
					if (!addedBottom){
						if (sameColour(x, y+1, imageData, context)){
							pixelStack.push([x, y+1]);
							addedBottom = true;
						}
					}else if(!sameColour(x, y+1, imageData, context)){
							addedBottom = false;
					}
				}
				--x;
				if (x < farRight){
					farRight = x;
				}

				

				

				x = farLeft;
				while (y >= highest){
					if (!addedLeft){
						if (sameColour(x-1, y, imageData, context)){
							pixelStack.push([x-1,y]);
							addedLeft = true;
						}
					}else if(!sameColour(x-1, y, imageData, context)){
						addedLeft = false;
					}
					--y;
				}

				x = farRight;
				while (y <= lowest){
					if (!addedRight){
						if (sameColour(x+1, y, imageData, context)){
							pixelStack.push([x+1, y]);
							addedRight = true;
						}else if(!sameColour(x+1, y, imageData, context)){
							addedRight = false;
						}
					}
					++y;
				}

				console.log("Left " + farLeft);
				console.log("Right " + farRight);
				console.log("Highest " + highest);
				console.log("Lowest " + lowest);
				newdata = context.createImageData(farRight - farLeft + 1, lowest - highest + 1);
				for (i = 0; i < newdata.data.length; ++i){
					switch(i%4){
						case 0:
							newdata.data[i] = 4;
							break;
						case 1:
							newdata.data[i] = 128;
							break;
						case 2:
							newdata.data[i] = 80;
							break;
						case 3:
							newdata.data[i] = 255;
					}
				}
				//context.putImageData(newdata, x, y++)
				context.putImageData(newdata, farLeft, highest);
			}


		}
		*/

//////////////////////////////////////////////////////////////////////////////////
//																				//
//																				//
//									JQUERY										//
//																				//
//																				//
//////////////////////////////////////////////////////////////////////////////////


$(document).ready(function(){
	$('#myCanvas').on('mousedown', function(){
		timeout = setTimeout(function() {
			alert('lol');
			clearTimeout(timeout);
		}, 200);
	}).on('mouseup mouseleave', function() {
		clearTimeout(timeout);
	});
});