<?php

namespace App\View\Components\Layouts;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

class Sidebar extends Component
{
    /**
     * The user's role
     */
    public string $role;

    /**
     * The sidebar menu items
     */
    public array $menuItems;

    /**
     * Create a new component instance.
     */
    public function __construct()
    {
        $user = Auth::user();
        $this->role = $user->roles->first()?->name ?? 'respondent';
        $this->menuItems = $this->propagateActive($this->getMenuItemsByRole($this->role));
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.layouts.sidebar');
    }

    /**
     * Get menu items based on user role
     */
    private function getMenuItemsByRole(string $role): array
    {
        return match($role) {
            'admin' => $this->getAdminItems(),
            'faculty_dean' => $this->getFacultyDeanItems(),
            'quality_manager' => $this->getQualityManagerItems(),
            'respondent' => $this->getRespondentItems(),
            default => []
        };
    }

    /**
     * Get super admin menu items
     */
    private function getAdminItems(): array
    {
        return [
            [
                'title' => __('Dashboard'),
                'icon' => 'dashboard',
                'link' => route('admin.home'),
            ],
            [
                'title' => __('Faculties'), 
                'icon' => 'widgets',

                'link' => route('academic.faculties.index')
            ],
            [
                'title' => __('Faculty Members'), 
                'icon' => 'widgets',

                'link' => route('academic.faculty-member.index'),
                'active' => request()->route()?->getName() === 'academic.faculty-member.show',
            ],
            [
                'title' => __('Students'),
                'icon' => 'widgets',
                'link' => route('academic.student.index')
            ],
            [
                'title' => __('Courses'),
                'icon' => 'widgets',

                'link' => route('academic.courses.index'),
                'active' => request()->route()?->getName() === 'academic.courses.show',
            ],
            [
                'title' => __('Enrollments'), 
                'icon' => 'widgets',

                'link' => route('academic.enrollments.index')
            ],
            [
                'title' => __('Questions'),
                'icon' => 'form_elements',
                'submenu' => [
                    [
                        'title' => __('All Questions'), 
                        'link' => route('questions.index')
                    ],
                    [
                        'title' => __('Create New'), 
                        'link' => route('questions.create')
                    ],
                ]
            ],
            [
                'title' => __('Questionnaire Templates'),
                'icon' => 'components',
                'submenu' => [
                    [
                        'title' => __('All Templates'), 
                        'link' => route('questionnaire.template.index'),
                        'active' => request()->route()?->getName() === 'questionnaire.template.show',

                    ],
                    [
                        'title' => __('Create New'), 
                        'link' => route('questionnaire.template.create')
                    ],
                   
                ]
            ],
            [
                'title' => __('Questionnaire Deployments'),
                'icon' => 'basic',
                'submenu' => [
                    [
                        'title' => __('All Deployments'), 
                        'link' => route('questionnaires.deployed.index'),
                        'active' => request()->route()?->getName() === 'response.report',
                    ],
                    [
                        'title' => __('Create New'), 
                        'link' => route('questionnaires.deployed.create')
                    ],
                ]
            ],
            [
                'title' => __('Users Management'),
                'icon' => 'user',
                'submenu' => [
                    ['title' => __('Admins'), 'link' => route('users.admin.index')]
                ]
            ]
        ];
    }

    /**
     * Get faculty-dean menu items
     */
    private function getFacultyDeanItems(): array
    {
        return [
            [
                'title' => __('Dashboard'),
                'icon' => 'dashboard',
                'link' => route('faculty-dean.home'),
            ],
            [
                'title' => __('Courses'),
                'icon' => 'widgets',

                'link' => route('academic.courses.index'),
                'active' => request()->route()?->getName() === 'academic.courses.show',

            ],
            [
                'title' => __('Enrollments'), 
                'icon' => 'widgets',

                'link' => route('academic.enrollments.index')
            ],
            [
                'title' => __('Questionnaire Deployments'),
                'icon' => 'basic',
                'submenu' => [
                    [
                        'title' => __('All Deployments'), 
                        'link' => route('questionnaires.deployed.index')
                    ],
                ]
            ],
        ];
    }

    /**
     * Get quality manager menu items
     */
    private function getQualityManagerItems(): array
    {
        return [
            [
                'title' => __('Dashboard'),
                'icon' => 'dashboard',
                'link' => route('quality-manager.home'),
            ],
            [
                'title' => __('Questionnaire Deployments'),
                'icon' => 'basic',
                'submenu' => [
                    [
                        'title' => __('All Deployments'), 
                        'link' => route('questionnaires.deployed.index')
                    ],
                ]
            ],
        ];
    }

    /**
     * Get respondent menu items
     */
    private function getRespondentItems(): array
    {
        return [
            [
                'title' => __('Dashboard'),
                'icon' => 'dashboard',
                'link' => route('respondent.home'),
            ]
        ];
    }


    private function propagateActive(array $items): array
    {
        foreach ($items as $i => $item) {
            $hasActiveChild = false;

            // If this item has a submenu, check its children
            if (isset($item['submenu'])) {
                $item['submenu'] = $this->propagateActive($item['submenu']);
                foreach ($item['submenu'] as $sub) {
                    if (!empty($sub['active'])) {
                        $hasActiveChild = true;
                        break;
                    }
                }
            }

            // If this item is active or has an active child, mark as active
            if (!empty($item['active']) || $hasActiveChild) {
                $item['active'] = true;
            }

            $items[$i] = $item;
        }
        return $items;
    }
}