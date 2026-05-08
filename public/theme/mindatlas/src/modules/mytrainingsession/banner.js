import '@scss/layouts/mytrainingsessions.scss';
import React from 'react';

const BannerTrainingSession = ({ title }) => {
  let sectionWelcomeStyle = {
    backgroundImage: 'url("' + M.theme.brand.my_training_banner + '")',
    marginTop: '100px',
  };
  return (
    <section id="section-banner" className="mt-navbar page-banner" style={sectionWelcomeStyle}>
      <div className="banner-box">
        <h1
          className="page-title"
          style={{ color: 'white', fontWeight: '700', fontSize: '4rem', paddingLeft: '9rem', paddingTop: '4rem' }}>
          {title}
        </h1>
      </div>
    </section>
  );
};

export default BannerTrainingSession;
