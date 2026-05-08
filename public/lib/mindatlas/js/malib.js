
function rotate_ios_photo(img) {
    var rotation = {
      1: 'rotate(0deg)',
      3: 'rotate(180deg)',
      6: 'rotate(90deg)',
      8: 'rotate(270deg)'
    };
  
    EXIF.getData(img, function() {
      var orientation = EXIF.getTag(this, "Orientation");
  
      // console.log(img);
      // console.log($(img).hasClass('rotated'))
      // console.log(rotation[orientation])
  
      if(rotation[orientation] && !$(img).hasClass('rotated')) {
        // rotate img
        if(orientation == 1 || orientation == 3 ) {
          $(img).css('transform', rotation[orientation]);    
        }else if(orientation == 6 || orientation == 8) {
          // $(img).wrap("<div class='rotated_img_wrapper'></div>");
          $(img).css('transform', rotation[orientation] + ' scale(0.7)');            
        }
        
        $(img).addClass('rotated')
      }
  
    });
  
  }
  
  