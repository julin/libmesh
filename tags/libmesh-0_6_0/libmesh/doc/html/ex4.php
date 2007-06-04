<?php $root=""; ?>
<?php require($root."navigation.php"); ?>
<html>
<head>
  <?php load_style($root); ?>
</head>
 
<body>
 
<?php make_navigation("ex4",$root)?>
 
<div class="content">
<a name="comments"></a> 
<div class = "comment">
<h1>Example 4 - Solving a 2D or 3D Poisson Problem in Parallel</h1>

<br><br>This is the fourth example program.  It builds on
the third example program by showing how to formulate
the code in a dimension-independent way.  Very minor
changes to the example will allow the problem to be
solved in two or three dimensions.

<br><br>This example will also introduce the PerfLog class
as a way to monitor your code's performance.  We will
use it to instrument the matrix assembly code and look
for bottlenecks where we should focus optimization efforts.

<br><br>This example also shows how to extend example 3 to run in
parallel.  Notice how litte has changed!  The significant
differences are marked with "PARALLEL CHANGE".


<br><br>

<br><br>C++ include files that we need
</div>

<div class ="fragment">
<pre>
        #include &lt;iostream&gt;
        #include &lt;algorithm&gt;
        #include &lt;math.h&gt;
        
</pre>
</div>
<div class = "comment">
Basic include file needed for the mesh functionality.
</div>

<div class ="fragment">
<pre>
        #include "libmesh.h"
        #include "mesh.h"
        #include "gmv_io.h"
        #include "implicit_system.h"
        #include "equation_systems.h"
        
</pre>
</div>
<div class = "comment">
Define the Finite Element object.
</div>

<div class ="fragment">
<pre>
        #include "fe.h"
        
</pre>
</div>
<div class = "comment">
Define Gauss quadrature rules.
</div>

<div class ="fragment">
<pre>
        #include "quadrature_gauss.h"
        
</pre>
</div>
<div class = "comment">
Define the DofMap, which handles degree of freedom
indexing.
</div>

<div class ="fragment">
<pre>
        #include "dof_map.h"
        
</pre>
</div>
<div class = "comment">
Define useful datatypes for finite element
matrix and vector components.
</div>

<div class ="fragment">
<pre>
        #include "sparse_matrix.h"
        #include "numeric_vector.h"
        #include "dense_matrix.h"
        #include "dense_vector.h"
        
</pre>
</div>
<div class = "comment">
Define the PerfLog, a performance logging utility.
It is useful for timing events in a code and giving
you an idea where bottlenecks lie.
</div>

<div class ="fragment">
<pre>
        #include "perf_log.h"
        
        
</pre>
</div>
<div class = "comment">
Function prototype.  This is the function that will assemble
the linear system for our Poisson problem.  Note that the
function will take the \p EquationSystems object and the
name of the system we are assembling as input.  From the
\p EquationSystems object we have acess to the \p Mesh and
other objects we might need.
</div>

<div class ="fragment">
<pre>
        void assemble_poisson(EquationSystems& es,
                              const std::string& system_name);
        
</pre>
</div>
<div class = "comment">
Exact solution function prototype.
</div>

<div class ="fragment">
<pre>
        Real exact_solution (const Real x,
        		     const Real y,
        		     const Real z = 0.);
        
</pre>
</div>
<div class = "comment">
Begin the main program.
</div>

<div class ="fragment">
<pre>
        int main (int argc, char** argv)
        {
</pre>
</div>
<div class = "comment">
Initialize libMesh and any dependent libaries, like in example 2.
</div>

<div class ="fragment">
<pre>
          libMesh::init (argc, argv);
        
</pre>
</div>
<div class = "comment">
Declare a performance log for the main program
PerfLog perf_main("Main Program");
  

<br><br>Braces are used to force object scope, like in example 2
</div>

<div class ="fragment">
<pre>
          {
</pre>
</div>
<div class = "comment">
Check for proper calling arguments.
</div>

<div class ="fragment">
<pre>
            if (argc &lt; 3)
              {
        	std::cerr &lt;&lt; "Usage: " &lt;&lt; argv[0] &lt;&lt; " -d 2(3)" &lt;&lt; " -n 15"
        		  &lt;&lt; std::endl;
        
</pre>
</div>
<div class = "comment">
This handy function will print the file name, line number,
and then abort.  Currrently the library does not use C++
exception handling.
</div>

<div class ="fragment">
<pre>
                error();
              }
            
</pre>
</div>
<div class = "comment">
Brief message to the user regarding the program name
and command line arguments.
</div>

<div class ="fragment">
<pre>
            else 
              {
        	std::cout &lt;&lt; "Running " &lt;&lt; argv[0];
        	
        	for (int i=1; i&lt;argc; i++)
        	  std::cout &lt;&lt; " " &lt;&lt; argv[i];
        	
        	std::cout &lt;&lt; std::endl &lt;&lt; std::endl;
              }
            
</pre>
</div>
<div class = "comment">
Get the dimensionality of the mesh from argv[2]
</div>

<div class ="fragment">
<pre>
            const unsigned int dim = atoi(argv[2]);     
            
</pre>
</div>
<div class = "comment">
Get the problem size from argv[4]
</div>

<div class ="fragment">
<pre>
            const unsigned int ps = atoi(argv[4]);
</pre>
</div>
<div class = "comment">
std::cout << "problem_size=" << ps << std::endl;
    

<br><br>Create a mesh with user-defined dimension.
</div>

<div class ="fragment">
<pre>
            Mesh mesh (dim);
            
        
</pre>
</div>
<div class = "comment">
Use the internal mesh generator to create a uniform
grid on the square [-1,1]^D.  We instruct the mesh generator
to build a mesh of 8x8 \p Quad9 elements in 2D, or \p Hex27
elements in 3D.  Building these higher-order elements allows
us to use higher-order approximation, as in example 3.
</div>

<div class ="fragment">
<pre>
            mesh.build_cube (ps, ps, ps,
        		     -1., 1.,
        		     -1., 1.,
        		     -1., 1.,
        		     (dim == 2) ? QUAD9 : HEX27);
        
</pre>
</div>
<div class = "comment">
Print information about the mesh to the screen.
</div>

<div class ="fragment">
<pre>
            mesh.print_info();
            
            
</pre>
</div>
<div class = "comment">
Create an equation systems object.
</div>

<div class ="fragment">
<pre>
            EquationSystems equation_systems (mesh);
            
</pre>
</div>
<div class = "comment">
Declare the system and its variables.
</div>

<div class ="fragment">
<pre>
            {
</pre>
</div>
<div class = "comment">
Creates a system named "Poisson"
</div>

<div class ="fragment">
<pre>
              equation_systems.add_system&lt;ImplicitSystem&gt; ("Poisson");
              
        
</pre>
</div>
<div class = "comment">
Adds the variable "u" to "Poisson".  "u"
will be approximated using second-order approximation.
</div>

<div class ="fragment">
<pre>
              equation_systems("Poisson").add_variable("u", SECOND);
        
</pre>
</div>
<div class = "comment">
Give the system a pointer to the matrix assembly
function.
</div>

<div class ="fragment">
<pre>
              equation_systems("Poisson").attach_assemble_function (assemble_poisson);
              
</pre>
</div>
<div class = "comment">
Initialize the data structures for the equation system.
</div>

<div class ="fragment">
<pre>
              equation_systems.init();
        
</pre>
</div>
<div class = "comment">
Prints information about the system to the screen.
</div>

<div class ="fragment">
<pre>
              equation_systems.print_info();
            }
        
</pre>
</div>
<div class = "comment">
Solve the system "Poisson", just like example 2.
</div>

<div class ="fragment">
<pre>
            equation_systems("Poisson").solve();
        
</pre>
</div>
<div class = "comment">
After solving the system write the solution
to a GMV-formatted plot file.
</div>

<div class ="fragment">
<pre>
            GMVIO (mesh).write_equation_systems ((dim == 3) ? "out_3.gmv" : "out_2.gmv",
        					 equation_systems);
          }
          
</pre>
</div>
<div class = "comment">
All done.  
</div>

<div class ="fragment">
<pre>
          return libMesh::close ();
        }
        
        
        
        
</pre>
</div>
<div class = "comment">

<br><br>
<br><br>
<br><br>We now define the matrix assembly function for the
Poisson system.  We need to first compute element
matrices and right-hand sides, and then take into
account the boundary conditions, which will be handled
via a penalty method.
</div>

<div class ="fragment">
<pre>
        void assemble_poisson(EquationSystems& es,
                              const std::string& system_name)
        {
</pre>
</div>
<div class = "comment">
It is a good idea to make sure we are assembling
the proper system.
</div>

<div class ="fragment">
<pre>
          assert (system_name == "Poisson");
        
        
</pre>
</div>
<div class = "comment">
Declare a performance log.  Give it a descriptive
string to identify what part of the code we are
logging, since there may be many PerfLogs in an
application.
</div>

<div class ="fragment">
<pre>
          PerfLog perf_log ("Matrix Assembly");
          
</pre>
</div>
<div class = "comment">
Get a constant reference to the mesh object.
</div>

<div class ="fragment">
<pre>
          const Mesh& mesh = es.get_mesh();
        
</pre>
</div>
<div class = "comment">
The dimension that we are running
</div>

<div class ="fragment">
<pre>
          const unsigned int dim = mesh.mesh_dimension();
        
</pre>
</div>
<div class = "comment">
Get a reference to the ImplicitSystem we are solving
</div>

<div class ="fragment">
<pre>
          ImplicitSystem& system = es.get_system&lt;ImplicitSystem&gt;("Poisson");
          
</pre>
</div>
<div class = "comment">
A reference to the \p DofMap object for this system.  The \p DofMap
object handles the index translation from node and element numbers
to degree of freedom numbers.  We will talk more about the \p DofMap
in future examples.
</div>

<div class ="fragment">
<pre>
          const DofMap& dof_map = system.get_dof_map();
        
</pre>
</div>
<div class = "comment">
Get a constant reference to the Finite Element type
for the first (and only) variable in the system.
</div>

<div class ="fragment">
<pre>
          FEType fe_type = dof_map.variable_type(0);
        
</pre>
</div>
<div class = "comment">
Build a Finite Element object of the specified type.  Since the
\p FEBase::build() member dynamically creates memory we will
store the object as an \p AutoPtr<FEBase>.  This can be thought
of as a pointer that will clean up after itself.
</div>

<div class ="fragment">
<pre>
          AutoPtr&lt;FEBase&gt; fe (FEBase::build(dim, fe_type));
          
</pre>
</div>
<div class = "comment">
A 5th order Gauss quadrature rule for numerical integration.
</div>

<div class ="fragment">
<pre>
          QGauss qrule (dim, FIFTH);
        
</pre>
</div>
<div class = "comment">
Tell the finite element object to use our quadrature rule.
</div>

<div class ="fragment">
<pre>
          fe-&gt;attach_quadrature_rule (&qrule);
        
</pre>
</div>
<div class = "comment">
Declare a special finite element object for
boundary integration.
</div>

<div class ="fragment">
<pre>
          AutoPtr&lt;FEBase&gt; fe_face (FEBase::build(dim, fe_type));
        	      
</pre>
</div>
<div class = "comment">
Boundary integration requires one quadraure rule,
with dimensionality one less than the dimensionality
of the element.
</div>

<div class ="fragment">
<pre>
          QGauss qface(dim-1, FIFTH);
          
</pre>
</div>
<div class = "comment">
Tell the finte element object to use our
quadrature rule.
</div>

<div class ="fragment">
<pre>
          fe_face-&gt;attach_quadrature_rule (&qface);
        
</pre>
</div>
<div class = "comment">
Here we define some references to cell-specific data that
will be used to assemble the linear system.
We begin with the element Jacobian * quadrature weight at each
integration point.   
</div>

<div class ="fragment">
<pre>
          const std::vector&lt;Real&gt;& JxW = fe-&gt;get_JxW();
        
</pre>
</div>
<div class = "comment">
The physical XY locations of the quadrature points on the element.
These might be useful for evaluating spatially varying material
properties at the quadrature points.
</div>

<div class ="fragment">
<pre>
          const std::vector&lt;Point&gt;& q_point = fe-&gt;get_xyz();
        
</pre>
</div>
<div class = "comment">
The element shape functions evaluated at the quadrature points.
</div>

<div class ="fragment">
<pre>
          const std::vector&lt;std::vector&lt;Real&gt; &gt;& phi = fe-&gt;get_phi();
        
</pre>
</div>
<div class = "comment">
The element shape function gradients evaluated at the quadrature
points.
</div>

<div class ="fragment">
<pre>
          const std::vector&lt;std::vector&lt;RealGradient&gt; &gt;& dphi = fe-&gt;get_dphi();
        
</pre>
</div>
<div class = "comment">
Define data structures to contain the element matrix
and right-hand-side vector contribution.  Following
basic finite element terminology we will denote these
"Ke" and "Fe". More detail is in example 3.
</div>

<div class ="fragment">
<pre>
          DenseMatrix&lt;Number&gt; Ke;
          DenseVector&lt;Number&gt; Fe;
        
</pre>
</div>
<div class = "comment">
This vector will hold the degree of freedom indices for
the element.  These define where in the global system
the element degrees of freedom get mapped.
</div>

<div class ="fragment">
<pre>
          std::vector&lt;unsigned int&gt; dof_indices;
        
</pre>
</div>
<div class = "comment">
Now we will loop over all the elements in the mesh.
We will compute the element matrix and right-hand-side
contribution.  See example 3 for a discussion of the
element iterators.  Here we use the \p const_local_elem_iterator
to indicate we only want to loop over elements that are assigned
to the local processor.  This allows each processor to compute
its components of the global matrix.

<br><br>"PARALLEL CHANGE"
const_local_elem_iterator           el (mesh.elements_begin());
const const_local_elem_iterator end_el (mesh.elements_end());


<br><br></div>

<div class ="fragment">
<pre>
          MeshBase::const_element_iterator       el     = mesh.local_elements_begin();
          const MeshBase::const_element_iterator end_el = mesh.local_elements_end();
        
          for ( ; el != end_el; ++el)
            {
</pre>
</div>
<div class = "comment">
Start logging the shape function initialization.
This is done through a simple function call with
the name of the event to log.
</div>

<div class ="fragment">
<pre>
              perf_log.start_event("elem init");      
        
</pre>
</div>
<div class = "comment">
Store a pointer to the element we are currently
working on.  This allows for nicer syntax later.
</div>

<div class ="fragment">
<pre>
              const Elem* elem = *el;
        
</pre>
</div>
<div class = "comment">
Get the degree of freedom indices for the
current element.  These define where in the global
matrix and right-hand-side this element will
contribute to.
</div>

<div class ="fragment">
<pre>
              dof_map.dof_indices (elem, dof_indices);
        
</pre>
</div>
<div class = "comment">
Compute the element-specific data for the current
element.  This involves computing the location of the
quadrature points (q_point) and the shape functions
(phi, dphi) for the current element.
</div>

<div class ="fragment">
<pre>
              fe-&gt;reinit (elem);
        
</pre>
</div>
<div class = "comment">
Zero the element matrix and right-hand side before
summing them.  We use the resize member here because
the number of degrees of freedom might have changed from
the last element.  Note that this will be the case if the
element type is different (i.e. the last element was a
triangle, now we are on a quadrilateral).
</div>

<div class ="fragment">
<pre>
              Ke.resize (dof_indices.size(),
        		 dof_indices.size());
        
              Fe.resize (dof_indices.size());
        
</pre>
</div>
<div class = "comment">
Stop logging the shape function initialization.
If you forget to stop logging an event the PerfLog
object will probably catch the error and abort.
</div>

<div class ="fragment">
<pre>
              perf_log.stop_event("elem init");      
        
</pre>
</div>
<div class = "comment">
Now we will build the element matrix.  This involves
a double loop to integrate the test funcions (i) against
the trial functions (j).

<br><br>We have split the numeric integration into two loops
so that we can log the matrix and right-hand-side
computation seperately.

<br><br>Now start logging the element matrix computation
</div>

<div class ="fragment">
<pre>
              perf_log.start_event ("Ke");
        
              for (unsigned int qp=0; qp&lt;qrule.n_points(); qp++)
        	for (unsigned int i=0; i&lt;phi.size(); i++)
        	  for (unsigned int j=0; j&lt;phi.size(); j++)
        	    Ke(i,j) += JxW[qp]*(dphi[i][qp]*dphi[j][qp]);
        	    
        
</pre>
</div>
<div class = "comment">
Stop logging the matrix computation
</div>

<div class ="fragment">
<pre>
              perf_log.stop_event ("Ke");
        
</pre>
</div>
<div class = "comment">
Now we build the element right-hand-side contribution.
This involves a single loop in which we integrate the
"forcing function" in the PDE against the test functions.

<br><br>Start logging the right-hand-side computation
</div>

<div class ="fragment">
<pre>
              perf_log.start_event ("Fe");
              
              for (unsigned int qp=0; qp&lt;qrule.n_points(); qp++)
        	{
</pre>
</div>
<div class = "comment">
fxy is the forcing function for the Poisson equation.
In this case we set fxy to be a finite difference
Laplacian approximation to the (known) exact solution.

<br><br>We will use the second-order accurate FD Laplacian
approximation, which in 2D on a structured grid is

<br><br>u_xx + u_yy = (u(i-1,j) + u(i+1,j) +
u(i,j-1) + u(i,j+1) +
-4*u(i,j))/h^2

<br><br>Since the value of the forcing function depends only
on the location of the quadrature point (q_point[qp])
we will compute it here, outside of the i-loop	  
</div>

<div class ="fragment">
<pre>
                  const Real x = q_point[qp](0);
        	  const Real y = q_point[qp](1);
        	  const Real z = q_point[qp](2);
        	  const Real eps = 1.e-3;
        
        	  const Real uxx = (exact_solution(x-eps,y,z) +
        			    exact_solution(x+eps,y,z) +
        			    -2.*exact_solution(x,y,z))/eps/eps;
        	      
        	  const Real uyy = (exact_solution(x,y-eps,z) +
        			    exact_solution(x,y+eps,z) +
        			    -2.*exact_solution(x,y,z))/eps/eps;
        	  
        	  const Real uzz = (exact_solution(x,y,z-eps) +
        			    exact_solution(x,y,z+eps) +
        			    -2.*exact_solution(x,y,z))/eps/eps;
        
        	  const Real fxy = - (uxx + uyy + ((dim==2) ? 0. : uzz));
        
</pre>
</div>
<div class = "comment">
Add the RHS contribution
</div>

<div class ="fragment">
<pre>
                  for (unsigned int i=0; i&lt;phi.size(); i++)
        	    Fe(i) += JxW[qp]*fxy*phi[i][qp];	  
        	}
              
</pre>
</div>
<div class = "comment">
Stop logging the right-hand-side computation
</div>

<div class ="fragment">
<pre>
              perf_log.stop_event ("Fe");
        
</pre>
</div>
<div class = "comment">
At this point the interior element integration has
been completed.  However, we have not yet addressed
boundary conditions.  For this example we will only
consider simple Dirichlet boundary conditions imposed
via the penalty method. This is discussed at length in
example 3.
</div>

<div class ="fragment">
<pre>
              {
        	
</pre>
</div>
<div class = "comment">
Start logging the boundary condition computation
</div>

<div class ="fragment">
<pre>
                perf_log.start_event ("BCs");
        
</pre>
</div>
<div class = "comment">
The following loops over the sides of the element.
If the element has no neighbor on a side then that
side MUST live on a boundary of the domain.
</div>

<div class ="fragment">
<pre>
                for (unsigned int side=0; side&lt;elem-&gt;n_sides(); side++)
        	  if (elem-&gt;neighbor(side) == NULL)
        	    {
</pre>
</div>
<div class = "comment">
The value of the shape functions at the quadrature
points.
</div>

<div class ="fragment">
<pre>
                      const std::vector&lt;std::vector&lt;Real&gt; &gt;&  phi_face = fe_face-&gt;get_phi();
        
</pre>
</div>
<div class = "comment">
The Jacobian * Quadrature Weight at the quadrature
points on the face.
</div>

<div class ="fragment">
<pre>
                      const std::vector&lt;Real&gt;& JxW_face = fe_face-&gt;get_JxW();
        	      
</pre>
</div>
<div class = "comment">
The XYZ locations (in physical space) of the
quadrature points on the face.  This is where
we will interpolate the boundary value function.
</div>

<div class ="fragment">
<pre>
                      const std::vector&lt;Point &gt;& qface_point = fe_face-&gt;get_xyz();
        
</pre>
</div>
<div class = "comment">
Compute the shape function values on the element
face.
</div>

<div class ="fragment">
<pre>
                      fe_face-&gt;reinit(elem, side);
        	      
</pre>
</div>
<div class = "comment">
Loop over the face quagrature points for integration.
</div>

<div class ="fragment">
<pre>
                      for (unsigned int qp=0; qp&lt;qface.n_points(); qp++)
        		{
</pre>
</div>
<div class = "comment">
The location on the boundary of the current
face quadrature point.
</div>

<div class ="fragment">
<pre>
                          const Real xf = qface_point[qp](0);
        		  const Real yf = qface_point[qp](1);
        		  const Real zf = qface_point[qp](2);
        
</pre>
</div>
<div class = "comment">
The penalty value.  \frac{1}{\epsilon}
in the discussion above.
</div>

<div class ="fragment">
<pre>
                          const Real penalty = 1.e10;
        		  
</pre>
</div>
<div class = "comment">
The boundary value.
</div>

<div class ="fragment">
<pre>
                          const Real value = exact_solution(xf, yf, zf);
        		  
</pre>
</div>
<div class = "comment">
Matrix contribution of the L2 projection. 
</div>

<div class ="fragment">
<pre>
                          for (unsigned int i=0; i&lt;phi_face.size(); i++)
        		    for (unsigned int j=0; j&lt;phi_face.size(); j++)
        		      Ke(i,j) += JxW_face[qp]*penalty*phi_face[i][qp]*phi_face[j][qp];
        		  
</pre>
</div>
<div class = "comment">
Right-hand-side contribution of the L2
projection.
</div>

<div class ="fragment">
<pre>
                          for (unsigned int i=0; i&lt;phi_face.size(); i++)
        		    Fe(i) += JxW_face[qp]*penalty*value*phi_face[i][qp];
        		} 
        	    } 
        	
</pre>
</div>
<div class = "comment">
Stop logging the boundary condition computation
</div>

<div class ="fragment">
<pre>
                perf_log.stop_event ("BCs");
              } 
              
        
</pre>
</div>
<div class = "comment">
The element matrix and right-hand-side are now built
for this element.  Add them to the global matrix and
right-hand-side vector.  The \p PetscMatrix::add_matrix()
and \p PetscVector::add_vector() members do this for us.
Start logging the insertion of the local (element)
matrix and vector into the global matrix and vector
</div>

<div class ="fragment">
<pre>
              perf_log.start_event ("matrix insertion");
              
              system.matrix-&gt;add_matrix (Ke, dof_indices);
              system.rhs-&gt;add_vector    (Fe, dof_indices);
        
</pre>
</div>
<div class = "comment">
Start logging the insertion of the local (element)
matrix and vector into the global matrix and vector
</div>

<div class ="fragment">
<pre>
              perf_log.stop_event ("matrix insertion");
            }
        
</pre>
</div>
<div class = "comment">
That's it.  We don't need to do anything else to the
PerfLog.  When it goes out of scope (at this function return)
it will print its log to the screen. Pretty easy, huh?
</div>

<div class ="fragment">
<pre>
        }
</pre>
</div>

<a name="nocomments"></a> 
<br><br><br> <h1> The program without comments: </h1> 
<pre> 
  
  
  #include &lt;iostream&gt;
  #include &lt;algorithm&gt;
  #include &lt;math.h&gt;
  
  #include <FONT COLOR="#BC8F8F"><B>&quot;libmesh.h&quot;</FONT></B>
  #include <FONT COLOR="#BC8F8F"><B>&quot;mesh.h&quot;</FONT></B>
  #include <FONT COLOR="#BC8F8F"><B>&quot;gmv_io.h&quot;</FONT></B>
  #include <FONT COLOR="#BC8F8F"><B>&quot;implicit_system.h&quot;</FONT></B>
  #include <FONT COLOR="#BC8F8F"><B>&quot;equation_systems.h&quot;</FONT></B>
  
  #include <FONT COLOR="#BC8F8F"><B>&quot;fe.h&quot;</FONT></B>
  
  #include <FONT COLOR="#BC8F8F"><B>&quot;quadrature_gauss.h&quot;</FONT></B>
  
  #include <FONT COLOR="#BC8F8F"><B>&quot;dof_map.h&quot;</FONT></B>
  
  #include <FONT COLOR="#BC8F8F"><B>&quot;sparse_matrix.h&quot;</FONT></B>
  #include <FONT COLOR="#BC8F8F"><B>&quot;numeric_vector.h&quot;</FONT></B>
  #include <FONT COLOR="#BC8F8F"><B>&quot;dense_matrix.h&quot;</FONT></B>
  #include <FONT COLOR="#BC8F8F"><B>&quot;dense_vector.h&quot;</FONT></B>
  
  #include <FONT COLOR="#BC8F8F"><B>&quot;perf_log.h&quot;</FONT></B>
  
  
  <FONT COLOR="#228B22"><B>void</FONT></B> assemble_poisson(EquationSystems&amp; es,
                        <FONT COLOR="#228B22"><B>const</FONT></B> std::string&amp; system_name);
  
  Real exact_solution (<FONT COLOR="#228B22"><B>const</FONT></B> Real x,
  		     <FONT COLOR="#228B22"><B>const</FONT></B> Real y,
  		     <FONT COLOR="#228B22"><B>const</FONT></B> Real z = 0.);
  
  <FONT COLOR="#228B22"><B>int</FONT></B> main (<FONT COLOR="#228B22"><B>int</FONT></B> argc, <FONT COLOR="#228B22"><B>char</FONT></B>** argv)
  {
    libMesh::init (argc, argv);
  
    
    {
      <B><FONT COLOR="#A020F0">if</FONT></B> (argc &lt; 3)
        {
  	std::cerr &lt;&lt; <FONT COLOR="#BC8F8F"><B>&quot;Usage: &quot;</FONT></B> &lt;&lt; argv[0] &lt;&lt; <FONT COLOR="#BC8F8F"><B>&quot; -d 2(3)&quot;</FONT></B> &lt;&lt; <FONT COLOR="#BC8F8F"><B>&quot; -n 15&quot;</FONT></B>
  		  &lt;&lt; std::endl;
  
  	error();
        }
      
      <B><FONT COLOR="#A020F0">else</FONT></B> 
        {
  	std::cout &lt;&lt; <FONT COLOR="#BC8F8F"><B>&quot;Running &quot;</FONT></B> &lt;&lt; argv[0];
  	
  	<B><FONT COLOR="#A020F0">for</FONT></B> (<FONT COLOR="#228B22"><B>int</FONT></B> i=1; i&lt;argc; i++)
  	  std::cout &lt;&lt; <FONT COLOR="#BC8F8F"><B>&quot; &quot;</FONT></B> &lt;&lt; argv[i];
  	
  	std::cout &lt;&lt; std::endl &lt;&lt; std::endl;
        }
      
      <FONT COLOR="#228B22"><B>const</FONT></B> <FONT COLOR="#228B22"><B>unsigned</FONT></B> <FONT COLOR="#228B22"><B>int</FONT></B> dim = atoi(argv[2]);     
      
      <FONT COLOR="#228B22"><B>const</FONT></B> <FONT COLOR="#228B22"><B>unsigned</FONT></B> <FONT COLOR="#228B22"><B>int</FONT></B> ps = atoi(argv[4]);
      
      Mesh mesh (dim);
      
  
      mesh.build_cube (ps, ps, ps,
  		     -1., 1.,
  		     -1., 1.,
  		     -1., 1.,
  		     (dim == 2) ? QUAD9 : HEX27);
  
      mesh.print_info();
      
      
      EquationSystems equation_systems (mesh);
      
      {
        equation_systems.add_system&lt;ImplicitSystem&gt; (<FONT COLOR="#BC8F8F"><B>&quot;Poisson&quot;</FONT></B>);
        
  
        equation_systems(<FONT COLOR="#BC8F8F"><B>&quot;Poisson&quot;</FONT></B>).add_variable(<FONT COLOR="#BC8F8F"><B>&quot;u&quot;</FONT></B>, SECOND);
  
        equation_systems(<FONT COLOR="#BC8F8F"><B>&quot;Poisson&quot;</FONT></B>).attach_assemble_function (assemble_poisson);
        
        equation_systems.init();
  
        equation_systems.print_info();
      }
  
      equation_systems(<FONT COLOR="#BC8F8F"><B>&quot;Poisson&quot;</FONT></B>).solve();
  
      GMVIO (mesh).write_equation_systems ((dim == 3) ? <FONT COLOR="#BC8F8F"><B>&quot;out_3.gmv&quot;</FONT></B> : <FONT COLOR="#BC8F8F"><B>&quot;out_2.gmv&quot;</FONT></B>,
  					 equation_systems);
    }
    
    <B><FONT COLOR="#A020F0">return</FONT></B> libMesh::close ();
  }
  
  
  
  
  <FONT COLOR="#228B22"><B>void</FONT></B> assemble_poisson(EquationSystems&amp; es,
                        <FONT COLOR="#228B22"><B>const</FONT></B> std::string&amp; system_name)
  {
    assert (system_name == <FONT COLOR="#BC8F8F"><B>&quot;Poisson&quot;</FONT></B>);
  
  
    PerfLog perf_log (<FONT COLOR="#BC8F8F"><B>&quot;Matrix Assembly&quot;</FONT></B>);
    
    <FONT COLOR="#228B22"><B>const</FONT></B> Mesh&amp; mesh = es.get_mesh();
  
    <FONT COLOR="#228B22"><B>const</FONT></B> <FONT COLOR="#228B22"><B>unsigned</FONT></B> <FONT COLOR="#228B22"><B>int</FONT></B> dim = mesh.mesh_dimension();
  
    ImplicitSystem&amp; system = es.get_system&lt;ImplicitSystem&gt;(<FONT COLOR="#BC8F8F"><B>&quot;Poisson&quot;</FONT></B>);
    
    <FONT COLOR="#228B22"><B>const</FONT></B> DofMap&amp; dof_map = system.get_dof_map();
  
    FEType fe_type = dof_map.variable_type(0);
  
    AutoPtr&lt;FEBase&gt; fe (FEBase::build(dim, fe_type));
    
    QGauss qrule (dim, FIFTH);
  
    fe-&gt;attach_quadrature_rule (&amp;qrule);
  
    AutoPtr&lt;FEBase&gt; fe_face (FEBase::build(dim, fe_type));
  	      
    QGauss qface(dim-1, FIFTH);
    
    fe_face-&gt;attach_quadrature_rule (&amp;qface);
  
    <FONT COLOR="#228B22"><B>const</FONT></B> std::vector&lt;Real&gt;&amp; JxW = fe-&gt;get_JxW();
  
    <FONT COLOR="#228B22"><B>const</FONT></B> std::vector&lt;Point&gt;&amp; q_point = fe-&gt;get_xyz();
  
    <FONT COLOR="#228B22"><B>const</FONT></B> std::vector&lt;std::vector&lt;Real&gt; &gt;&amp; phi = fe-&gt;get_phi();
  
    <FONT COLOR="#228B22"><B>const</FONT></B> std::vector&lt;std::vector&lt;RealGradient&gt; &gt;&amp; dphi = fe-&gt;get_dphi();
  
    DenseMatrix&lt;Number&gt; Ke;
    DenseVector&lt;Number&gt; Fe;
  
    std::vector&lt;<FONT COLOR="#228B22"><B>unsigned</FONT></B> <FONT COLOR="#228B22"><B>int</FONT></B>&gt; dof_indices;
  
  
    MeshBase::const_element_iterator       el     = mesh.local_elements_begin();
    <FONT COLOR="#228B22"><B>const</FONT></B> MeshBase::const_element_iterator end_el = mesh.local_elements_end();
  
    <B><FONT COLOR="#A020F0">for</FONT></B> ( ; el != end_el; ++el)
      {
        perf_log.start_event(<FONT COLOR="#BC8F8F"><B>&quot;elem init&quot;</FONT></B>);      
  
        <FONT COLOR="#228B22"><B>const</FONT></B> Elem* elem = *el;
  
        dof_map.dof_indices (elem, dof_indices);
  
        fe-&gt;reinit (elem);
  
        Ke.resize (dof_indices.size(),
  		 dof_indices.size());
  
        Fe.resize (dof_indices.size());
  
        perf_log.stop_event(<FONT COLOR="#BC8F8F"><B>&quot;elem init&quot;</FONT></B>);      
  
        perf_log.start_event (<FONT COLOR="#BC8F8F"><B>&quot;Ke&quot;</FONT></B>);
  
        <B><FONT COLOR="#A020F0">for</FONT></B> (<FONT COLOR="#228B22"><B>unsigned</FONT></B> <FONT COLOR="#228B22"><B>int</FONT></B> qp=0; qp&lt;qrule.n_points(); qp++)
  	<B><FONT COLOR="#A020F0">for</FONT></B> (<FONT COLOR="#228B22"><B>unsigned</FONT></B> <FONT COLOR="#228B22"><B>int</FONT></B> i=0; i&lt;phi.size(); i++)
  	  <B><FONT COLOR="#A020F0">for</FONT></B> (<FONT COLOR="#228B22"><B>unsigned</FONT></B> <FONT COLOR="#228B22"><B>int</FONT></B> j=0; j&lt;phi.size(); j++)
  	    Ke(i,j) += JxW[qp]*(dphi[i][qp]*dphi[j][qp]);
  	    
  
        perf_log.stop_event (<FONT COLOR="#BC8F8F"><B>&quot;Ke&quot;</FONT></B>);
  
        perf_log.start_event (<FONT COLOR="#BC8F8F"><B>&quot;Fe&quot;</FONT></B>);
        
        <B><FONT COLOR="#A020F0">for</FONT></B> (<FONT COLOR="#228B22"><B>unsigned</FONT></B> <FONT COLOR="#228B22"><B>int</FONT></B> qp=0; qp&lt;qrule.n_points(); qp++)
  	{
  	  <FONT COLOR="#228B22"><B>const</FONT></B> Real x = q_point[qp](0);
  	  <FONT COLOR="#228B22"><B>const</FONT></B> Real y = q_point[qp](1);
  	  <FONT COLOR="#228B22"><B>const</FONT></B> Real z = q_point[qp](2);
  	  <FONT COLOR="#228B22"><B>const</FONT></B> Real eps = 1.e-3;
  
  	  <FONT COLOR="#228B22"><B>const</FONT></B> Real uxx = (exact_solution(x-eps,y,z) +
  			    exact_solution(x+eps,y,z) +
  			    -2.*exact_solution(x,y,z))/eps/eps;
  	      
  	  <FONT COLOR="#228B22"><B>const</FONT></B> Real uyy = (exact_solution(x,y-eps,z) +
  			    exact_solution(x,y+eps,z) +
  			    -2.*exact_solution(x,y,z))/eps/eps;
  	  
  	  <FONT COLOR="#228B22"><B>const</FONT></B> Real uzz = (exact_solution(x,y,z-eps) +
  			    exact_solution(x,y,z+eps) +
  			    -2.*exact_solution(x,y,z))/eps/eps;
  
  	  <FONT COLOR="#228B22"><B>const</FONT></B> Real fxy = - (uxx + uyy + ((dim==2) ? 0. : uzz));
  
  	  <B><FONT COLOR="#A020F0">for</FONT></B> (<FONT COLOR="#228B22"><B>unsigned</FONT></B> <FONT COLOR="#228B22"><B>int</FONT></B> i=0; i&lt;phi.size(); i++)
  	    Fe(i) += JxW[qp]*fxy*phi[i][qp];	  
  	}
        
        perf_log.stop_event (<FONT COLOR="#BC8F8F"><B>&quot;Fe&quot;</FONT></B>);
  
        {
  	
  	perf_log.start_event (<FONT COLOR="#BC8F8F"><B>&quot;BCs&quot;</FONT></B>);
  
  	<B><FONT COLOR="#A020F0">for</FONT></B> (<FONT COLOR="#228B22"><B>unsigned</FONT></B> <FONT COLOR="#228B22"><B>int</FONT></B> side=0; side&lt;elem-&gt;n_sides(); side++)
  	  <B><FONT COLOR="#A020F0">if</FONT></B> (elem-&gt;neighbor(side) == NULL)
  	    {
  	      <FONT COLOR="#228B22"><B>const</FONT></B> std::vector&lt;std::vector&lt;Real&gt; &gt;&amp;  phi_face = fe_face-&gt;get_phi();
  
  	      <FONT COLOR="#228B22"><B>const</FONT></B> std::vector&lt;Real&gt;&amp; JxW_face = fe_face-&gt;get_JxW();
  	      
  	      <FONT COLOR="#228B22"><B>const</FONT></B> std::vector&lt;Point &gt;&amp; qface_point = fe_face-&gt;get_xyz();
  
  	      fe_face-&gt;reinit(elem, side);
  	      
  	      <B><FONT COLOR="#A020F0">for</FONT></B> (<FONT COLOR="#228B22"><B>unsigned</FONT></B> <FONT COLOR="#228B22"><B>int</FONT></B> qp=0; qp&lt;qface.n_points(); qp++)
  		{
  		  <FONT COLOR="#228B22"><B>const</FONT></B> Real xf = qface_point[qp](0);
  		  <FONT COLOR="#228B22"><B>const</FONT></B> Real yf = qface_point[qp](1);
  		  <FONT COLOR="#228B22"><B>const</FONT></B> Real zf = qface_point[qp](2);
  
  		  <FONT COLOR="#228B22"><B>const</FONT></B> Real penalty = 1.e10;
  		  
  		  <FONT COLOR="#228B22"><B>const</FONT></B> Real value = exact_solution(xf, yf, zf);
  		  
  		  <B><FONT COLOR="#A020F0">for</FONT></B> (<FONT COLOR="#228B22"><B>unsigned</FONT></B> <FONT COLOR="#228B22"><B>int</FONT></B> i=0; i&lt;phi_face.size(); i++)
  		    <B><FONT COLOR="#A020F0">for</FONT></B> (<FONT COLOR="#228B22"><B>unsigned</FONT></B> <FONT COLOR="#228B22"><B>int</FONT></B> j=0; j&lt;phi_face.size(); j++)
  		      Ke(i,j) += JxW_face[qp]*penalty*phi_face[i][qp]*phi_face[j][qp];
  		  
  		  <B><FONT COLOR="#A020F0">for</FONT></B> (<FONT COLOR="#228B22"><B>unsigned</FONT></B> <FONT COLOR="#228B22"><B>int</FONT></B> i=0; i&lt;phi_face.size(); i++)
  		    Fe(i) += JxW_face[qp]*penalty*value*phi_face[i][qp];
  		} 
  	    } 
  	
  	perf_log.stop_event (<FONT COLOR="#BC8F8F"><B>&quot;BCs&quot;</FONT></B>);
        } 
        
  
        perf_log.start_event (<FONT COLOR="#BC8F8F"><B>&quot;matrix insertion&quot;</FONT></B>);
        
        system.matrix-&gt;add_matrix (Ke, dof_indices);
        system.rhs-&gt;add_vector    (Fe, dof_indices);
  
        perf_log.stop_event (<FONT COLOR="#BC8F8F"><B>&quot;matrix insertion&quot;</FONT></B>);
      }
  
  }
</pre> 
<a name="output"></a> 
<br><br><br> <h1> The console output of the program: </h1> 
<pre>
Compiling C++ (in debug mode) ex4.C...
Linking ex4...
/home/peterson/code/libmesh/contrib/tecplot/lib/i686-pc-linux-gnu/tecio.a(tecxxx.o)(.text+0x1a7): In function `tecini':
: the use of `mktemp' is dangerous, better use `mkstemp'

***************************************************************
* Running Example  ./ex4
***************************************************************
 
Running ./ex4 -d 2 -n 15

 Mesh Information:
  mesh_dimension()=2
  spatial_dimension()=3
  n_nodes()=961
  n_elem()=225
   n_local_elem()=225
   n_active_elem()=225
  n_subdomains()=1
  n_processors()=1
  processor_id()=0

 EquationSystems
  n_systems()=1
   System "Poisson"
    Type "Implicit"
    Variables="u" 
    Finite Element Types="0", "12" 
    Infinite Element Mapping="0" 
    Approximation Orders="2", "3" 
    n_dofs()=961
    n_local_dofs()=961
    n_constrained_dofs()=0
    n_vectors()=1
  n_parameters()=2
   Parameters:
    "linear solver maximum iterations"=5000
    "linear solver tolerance"=1e-12


 ----------------------------------------------------------------------------
| Time:           Fri Nov 12 12:11:37 2004
| OS:             Linux
| HostName:       zaniwoop
| OS Release      2.4.20-19.9smp
| OS Version:     #1 SMP Tue Jul 15 17:04:18 EDT 2003
| Machine:        i686
| Username:       peterson
 ----------------------------------------------------------------------------
 ----------------------------------------------------------------------------
| Matrix Assembly Performance: Alive time=0.055975, Active time=0.052056
 ----------------------------------------------------------------------------
| Event                         nCalls  Total       Avg         Percent of   |
|                                       Time        Time        Active Time  |
|----------------------------------------------------------------------------|
|                                                                            |
| BCs                           225     0.0102      0.000045    19.66        |
| Fe                            225     0.0080      0.000035    15.28        |
| Ke                            225     0.0150      0.000067    28.91        |
| elem init                     225     0.0165      0.000073    31.67        |
| matrix insertion              225     0.0023      0.000010    4.48         |
 ----------------------------------------------------------------------------
| Totals:                       1125    0.0521                  100.00       |
 ----------------------------------------------------------------------------


 ---------------------------------------------------------------------------- 
| Reference count information                                                |
 ---------------------------------------------------------------------------- 
| 12SparseMatrixISt7complexIdEE reference count information:
|  Creations:    1
|  Destructions: 1
| 13NumericVectorISt7complexIdEE reference count information:
|  Creations:    3
|  Destructions: 3
| 21LinearSolverInterfaceISt7complexIdEE reference count information:
|  Creations:    1
|  Destructions: 1
| 4Elem reference count information:
|  Creations:    1185
|  Destructions: 1185
| 4Node reference count information:
|  Creations:    961
|  Destructions: 961
| 5QBase reference count information:
|  Creations:    3
|  Destructions: 3
| 6DofMap reference count information:
|  Creations:    1
|  Destructions: 1
| 6FEBase reference count information:
|  Creations:    2
|  Destructions: 2
| 6System reference count information:
|  Creations:    1
|  Destructions: 1
 ---------------------------------------------------------------------------- 
Running ./ex4 -d 3 -n 6

 Mesh Information:
  mesh_dimension()=3
  spatial_dimension()=3
  n_nodes()=2197
  n_elem()=216
   n_local_elem()=216
   n_active_elem()=216
  n_subdomains()=1
  n_processors()=1
  processor_id()=0

 EquationSystems
  n_systems()=1
   System "Poisson"
    Type "Implicit"
    Variables="u" 
    Finite Element Types="0", "12" 
    Infinite Element Mapping="0" 
    Approximation Orders="2", "3" 
    n_dofs()=2197
    n_local_dofs()=2197
    n_constrained_dofs()=0
    n_vectors()=1
  n_parameters()=2
   Parameters:
    "linear solver maximum iterations"=5000
    "linear solver tolerance"=1e-12


 ----------------------------------------------------------------------------
| Time:           Fri Nov 12 12:11:42 2004
| OS:             Linux
| HostName:       zaniwoop
| OS Release      2.4.20-19.9smp
| OS Version:     #1 SMP Tue Jul 15 17:04:18 EDT 2003
| Machine:        i686
| Username:       peterson
 ----------------------------------------------------------------------------
 ----------------------------------------------------------------------------
| Matrix Assembly Performance: Alive time=0.842618, Active time=0.83894
 ----------------------------------------------------------------------------
| Event                         nCalls  Total       Avg         Percent of   |
|                                       Time        Time        Active Time  |
|----------------------------------------------------------------------------|
|                                                                            |
| BCs                           216     0.2950      0.001366    35.16        |
| Fe                            216     0.0247      0.000115    2.95         |
| Ke                            216     0.3649      0.001689    43.50        |
| elem init                     216     0.1383      0.000640    16.49        |
| matrix insertion              216     0.0159      0.000074    1.90         |
 ----------------------------------------------------------------------------
| Totals:                       1080    0.8389                  100.00       |
 ----------------------------------------------------------------------------


 ---------------------------------------------------------------------------- 
| Reference count information                                                |
 ---------------------------------------------------------------------------- 
| 12SparseMatrixISt7complexIdEE reference count information:
|  Creations:    1
|  Destructions: 1
| 13NumericVectorISt7complexIdEE reference count information:
|  Creations:    3
|  Destructions: 3
| 21LinearSolverInterfaceISt7complexIdEE reference count information:
|  Creations:    1
|  Destructions: 1
| 4Elem reference count information:
|  Creations:    1728
|  Destructions: 1728
| 4Node reference count information:
|  Creations:    2197
|  Destructions: 2197
| 5QBase reference count information:
|  Creations:    4
|  Destructions: 4
| 6DofMap reference count information:
|  Creations:    1
|  Destructions: 1
| 6FEBase reference count information:
|  Creations:    2
|  Destructions: 2
| 6System reference count information:
|  Creations:    1
|  Destructions: 1
 ---------------------------------------------------------------------------- 
 
***************************************************************
* Done Running Example  ./ex4
***************************************************************
</pre>
</div>
<?php make_footer() ?>
</body>
</html>
<?php if (0) { ?>
\#Local Variables:
\#mode: html
\#End:
<?php } ?>
