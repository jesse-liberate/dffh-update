import React from "react";
import DropdownTreeSelect from "react-dropdown-tree-select";


const assignObjectPaths = (obj, stack) => {
  const isArray = Array.isArray(obj);
  Object.keys(obj).forEach(k => {
    const node = obj[k];
    const key = isArray ? `[${k}]` : k;

    if (typeof node === "object") {
      node.path = stack ? `${stack}.${key}` : key;
      assignObjectPaths(node, node.path);
    }
  });
};

const HOC = Wrapped => ({ data, ...rest }) => {
  assignObjectPaths(data);

  return <Wrapped data={data} {...rest} />;
};

export default HOC(DropdownTreeSelect);
