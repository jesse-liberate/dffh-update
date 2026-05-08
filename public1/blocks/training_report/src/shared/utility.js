export const updateObject = (oldObject, updatedProperties) => {
  return {
      ...oldObject,
      ...updatedProperties
  }
}

/**
 * Receives URL
 * Use browser to render the URL, style - width: 120px; height: auto;
 * 
 */
export function imgUrlToImgData(url) {
  return new Promise((resolve) => {
    const img = document.createElement('img');
    // const targetWidth = 150;
    // let targetHeight = 0;
    const targetHeight = 40;
    let targetWidth = 0;
    img.addEventListener('load', (e) => {
      // targetHeight = img.height * targetWidth / img.width;
      targetWidth = img.width * targetHeight / img.height
      const dataUrl = getDataUrl(e.currentTarget);
      // console.log(dataUrl);
      // document.body.append(img);

      resolve({
        dataUrl,
        width: targetWidth,
        height: targetHeight,
      });
    });
    img.src = url;
  });
}

export function getDataUrl(img) {
  const canvas = document.createElement('canvas');
  const ctx = canvas.getContext('2d');
  canvas.width = img.width;
  canvas.height = img.height;
  ctx.drawImage(img, 0, 0);
  return canvas.toDataURL('image/png');
}
