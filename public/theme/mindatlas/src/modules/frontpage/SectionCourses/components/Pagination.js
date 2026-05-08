import React, {Component} from 'react';

export class Pagination extends Component {
    render(){
        const {coursePerPage, totalCourses, paginate, nextPage, prevPage, currentPage} = this.props;
        
        const pageNumbers = [];

        const totalPages = Math.ceil(totalCourses/coursePerPage);

        const pageNeighbours = 2;

        for(let i = 1; i <= totalPages;i++){
            pageNumbers.push(i); 
        }

        return (
            <nav>
                <ul className="pagination">
                    <li className="page-item mx-2">
                        {currentPage == 1 ?
                        <a className="page-link"> &lt;</a>
                        :
                        <a className="page-link" onClick={()=>prevPage()}> &lt; </a>
                        }
                    </li>
                    {pageNumbers.map(num => (
                        <li className="page-item mx-2" key={num}>
                            {num===currentPage ?
                            <a onClick={()=>paginate(num)} className="page-link-custom text-white bg-color-brand-2">{num}</a>
                            :
                            <a onClick={()=>paginate(num)} className="page-link-custom">{num}</a>
                            }
                        </li>
                    ))}
                    

                    <li className="page-item mx-2">
                        {currentPage == totalPages ?
                        <a className="page-link"> &gt;</a>
                        :
                        <a className="page-link" onClick={()=>nextPage()}> &gt; </a>
                        }
                        
                    </li>
                    
                </ul>
            </nav>
        )
    }
}

export default Pagination;