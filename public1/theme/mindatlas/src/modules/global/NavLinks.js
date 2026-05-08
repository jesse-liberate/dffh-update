import React from 'react';

export default function ListGroupNav(props) {
  const loggedIn = M.user.id == 0 ? false : true;
  const items = [
    {
      name: 'My homepage',
      shortname: 'dashboard',
      href: M.cfg.wwwroot + '/',
      requieLogin: true,
    },
    {
      name: 'My self-guided learning',
      shortname: 'my-courses',
      href: M.cfg.wwwroot + '/course',
      requieLogin: true,
    },
    {
      name: 'My training sessions',
      shortname: 'my-training-sessions',
      href: M.cfg.wwwroot + '/theme/mindatlas/pages/my_training_sessions.php',
      requieLogin: true,
    },
    {
      name: 'My coaching sessions',
      shortname: 'my-coaching-sessions',
      href: M.cfg.wwwroot + '/theme/mindatlas/pages/my_coaching_sessions.php',
      requieLogin: true,
    },
    {
      name: 'Form builder',
      shortname: 'my-form-builder',
      href: M.cfg.wwwroot + '/theme/mindatlas/pages/form-builder.php',
      requieLogin: true,
    },
    {
      name: 'My profile',
      shortname: 'my-profile',
      href: M.cfg.wwwroot + '/user/profile.php',
      requieLogin: true,
    },
    // {
    //   name: 'Request sessions',
    //   shortname: 'request-sessions',
    //   href: M.cfg.wwwroot + '/theme/mindatlas/pages/request_sessions.php',
    //   requieLogin: true,
    // },
  ];

  function rItem(item, index) {
    if (item.requieLogin && loggedIn == false) {
      return '';
    }

    let href = item.href;
    if (item.nonLoginHref && loggedIn == false) {
      href = item.nonLoginHref;
    }
    return (
      <a
        key={index}
        className={`${item.shortname} link-white mx-2 mx-lg-5 d-none d-md-inline-block font-weight-bold text-center text-nowrap`}
        href={href}>
        {item.name}
      </a>
    );
  }

  return (
    <div className="mr-2">
      {items.map((item, index) => {
        return rItem(item, index);
      })}
    </div>
  );
}
