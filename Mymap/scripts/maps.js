	function fillLargeCanvas(){
			$('#body_content').html("<div id=\"map\">\n" + 
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
    			fillCountries(canvas,context);
    			
  			};
  			imageObj.src = "images/stock/Simple_world_map.png";
		}

		// Algorithm for filling in a country in the map. 
		// Currently a bit slow. 
		// TODO: improve performance
		function fillCountries(canvas, context){
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
			pixelStack = [[1000, 150]];
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

			function dropPins(){
				style = "style=\"position: absolute; left: 70%; top: 14%; z-index: 2;\">";
				$("#map").append("<img src=\"images/stock/pin.png\" height=\"50\" width=\"40\" " + style);
				style = "style=\"position: absolute; left: 50%; top: 40%; z-index: 2;\">";
				$("#map").append("<img src=\"images/stock/pin.png\" height=\"50\" width=\"40\" " + style);
			}
		