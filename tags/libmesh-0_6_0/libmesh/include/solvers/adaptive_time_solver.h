// $Id: adaptive_time_solver.h,v 1.5 2007-05-22 20:31:54 jwpeterson Exp $

// The libMesh Finite Element Library.
// Copyright (C) 2002-2005  Benjamin S. Kirk, John W. Peterson
  
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



#ifndef __adaptive_time_solver_h__
#define __adaptive_time_solver_h__

// C++ includes

// Local includes
#include "time_solver.h"

// Forward declarations
class System;

/**
 * This class wraps another TimeSolver derived class, and compares the results
 * of timestepping with deltat and timestepping with 2*deltat to adjust
 * future timestep lengths.
 *
 * Currently this class only works on fully coupled Systems
 *
 * This class is part of the new DifferentiableSystem framework,
 * which is still experimental.  Users of this framework should
 * beware of bugs and future API changes.
 *
 * @author Roy H. Stogner 2007
 */

// ------------------------------------------------------------
// Solver class definition
class AdaptiveTimeSolver : public TimeSolver
{
public:
  /**
   * The type of system
   */
  typedef DifferentiableSystem sys_type;
  
  /**
   * The parent class
   */
  typedef TimeSolver Parent;
  
  /**
   * Constructor. Requires a reference to the system
   * to be solved.
   */
  AdaptiveTimeSolver (sys_type& s);
  
  /**
   * Destructor.
   */
  virtual ~AdaptiveTimeSolver ();

  virtual void init();

  virtual void reinit();

  virtual void solve();

  virtual void advance_timestep();

  /**
   * This method is passed on to the core_time_solver
   */
  virtual Real error_order () const;

  /**
   * This method is passed on to the core_time_solver
   */
  virtual bool element_residual (bool get_jacobian);

  /**
   * This method is passed on to the core_time_solver
   */
  virtual bool side_residual (bool get_jacobian);

  /**
   * An implicit linear or nonlinear solver to use at each timestep.
   */
  virtual AutoPtr<DiffSolver> &diff_solver();

  /**
   * This object is used to take timesteps
   */
  AutoPtr<TimeSolver> core_time_solver;

  /**
   * The error calculations can be done in
   * H0 (L2) norm with component_norm[var] = 0,
   * H1 norm with component_norm[var] = 1, 
   * H2 norm with component_norm[var] = 2, 
   * or most cheaply in l2 norm by just leaving component_norm empty
   */
  std::vector<unsigned char> component_norm;

  /**
   * If component_norms is non-empty, each variable's contribution to the error
   * of a system will also be scaled by component_scale[var], unless
   * component_scale is empty in which case all variables will be weighted
   * equally.
   */
  std::vector<float> component_scale;

  /**
   * This tolerance is the target error between double-deltat
   * and single-deltat timesteps, scaled by deltat
   */
  Real target_tolerance;

  /**
   * This tolerance is the maximum error between double-deltat
   * and single-deltat timesteps, scaled by deltat
   *
   * FIXME - this value is currently ignored!
   */
  Real upper_tolerance;

  /**
   * Do not allow the adaptive time solver to select deltat > max_deltat.
   * If you set max_deltat=0.0, then max_deltat=infty.
   */
  Real max_deltat;

  /**
   * Do not allow the adaptive time solver to select deltat < min_deltat.
   * The default value is 0.0.
   */
  Real min_deltat;
  
  /**
   * This flag, which is true by default, grows (shrinks) the timestep
   * based on the expected global accuracy of the timestepping scheme.
   * Global in this sense means the cumulative final-time accuracy of
   * the scheme.  For example, the backward Euler scheme's truncation
   * error is locally of order 2, so that after N timesteps of size
   * deltat, the result is first-order accurate.  If you set this to
   * false, you can grow (shrink) your timestep based on the local
   * accuracy rather than the global accuracy of the core TimeSolver.
   * Note that by setting this value to false you may fail to achieve
   * the predicted convergence in time of the underlying method, however
   * it may be possible to get more fine-grained control over step sizes
   * as well.
   */
  bool global_tolerance;
  
protected:

  /**
   * We need to store the value of the last deltat used, so
   * that advance_timestep() will increment the system time
   * correctly.
   */
  Real last_deltat;

  /**
   * A helper function to calculate error norms
   */
  Real calculate_norm(System &, NumericVector<Number> &);
};



#endif // #define __adaptive_time_solver_h__
