// $Id: mesh_tetgen_support.C 2233 2007-10-24 16:58:42Z benkirk $
 
// The libMesh Finite Element Library.
// Copyright (C) 2002-2007  Benjamin S. Kirk, John W. Peterson

// This library is free software; you can redistribute it and/or
// modify it under the terms of the GNU Lesser General Public
// License as published by the Free Software Foundation; either
// version 2.1 of the License, or (at your option) any later version.

// This library is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
// Lesser General Public License for more details.

// You should have received a copy of the GNU Lesser General Public
// License along with this library; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

#include <parallel.h>
#include <vector>

void unit_test ()
{
  std::vector<unsigned int> vals;

  Parallel::allgather (libMesh::processor_id(),
		       vals);

  std::cout << "vals=[ ";
  for (unsigned int i=0; i<vals.size(); i++)
    std::cout << vals[i] << " ";

  std::cout << "]" << std::endl;
}
