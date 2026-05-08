import jsPDF from 'jspdf';
import "jspdf-autotable";
self.onmessage = (e) => {
  // const text = e.data;
  // const doc = new jsPDF('p', 'cm', 'A4');
  // doc.text(text, 0, 10);
  const {
    orientation,
    unit,
    size,
    title,
    marginLeft,
    content,
    imgData,
    chartInfo
  } = e.data;
  const doc = new jsPDF(orientation, unit, size);


  let now = new Date();
  let y = now.getFullYear();
  let m = now.getMonth() + 1;
  let d = now.getDate();
  let mm = m < 10 ? '0' + m : m;
  let dd = d < 10 ? '0' + d : d;

  let date =  'Date of report: ' + y + '-' + mm + '-'+ dd;
  const { dataUrl, width: imgWidth, height: imgHeight } = imgData;

  doc.setFontSize(15);
  if(dataUrl){
    doc.addImage(dataUrl, 'PNG', 40, 40, imgWidth, imgHeight);
  }
  doc.text(title, 1000, 60);
  doc.text(date, 1000, 80);
  doc.addImage(chartInfo.pieDataUrl, chartInfo.imgType, chartInfo.pieDrawX, chartInfo.pieDrawY, chartInfo.pieDrawWidth, chartInfo.pieDrawHeight);
  doc.addPage();
  doc.addImage(chartInfo.barDataUrl, chartInfo.imgType, chartInfo.barDrawX, chartInfo.barDrawY, chartInfo.barDrawWidth, chartInfo.barDrawHeight);
  doc.addPage();
  doc.autoTable(content);

  const blob = doc.output('blob');
  self.postMessage(blob);
};
