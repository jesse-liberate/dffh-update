import React from "react";

export default function ListGroupNav() {
    const items = [
        {
            name: "Home",
            href: M.cfg.wwwroot,
            iconClass: "icon fa fa-home fa-fw",
            requieLogin: false,
        },
        {
            name: "My courses",
            href: M.cfg.wwwroot + "/course",
            iconClass: "icon fa fa-graduation-cap fa-fw",
            requieLogin: true,
        },
        {
            name: "My Profile",
            href: M.cfg.wwwroot + "/user/profile.php",
            iconClass: "icon fa fa-user-circle fa-fw",
            requieLogin: true,
        },
    ];

    function rItem(item, index) {
        if (item.requieLogin && M.user.id == 0) {
            return "";
        }

        return (
            <li key={index}>
                <a
                    className="list-group-item list-group-item-action"
                    href={item.href}
                >
                    <div className="ml-0">
                        <div className="media">
                            <span className="media-left">
                                <i
                                    className={item.iconClass}
                                    aria-hidden="true"
                                ></i>
                            </span>
                            <span className="media-body ">{item.name}</span>
                        </div>
                    </div>
                </a>
            </li>
        );
    }

    return (
        <>
            <div className="list-group mt-1" aria-label="Site nav">
                <ul>
                    {items.map((item, index) => {
                        return rItem(item, index);
                    })}
                </ul>
            </div>
        </>
    );
}
