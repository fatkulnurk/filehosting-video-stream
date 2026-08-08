<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Contracts\Services\Admin\AdminServiceInterface;
use App\Contracts\Services\Directories\DirectoryServiceInterface;
use App\Contracts\Services\Files\FileServiceInterface;
use App\Contracts\Services\Subscriptions\SubscriptionServiceInterface;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminUserIndexRequest;
use App\Http\Requests\Admin\ChangeRoleRequest;
use App\Http\Requests\Admin\SetQuotaRequest;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Traits\AppliesTableParams;
use Illuminate\Support\Facades\Concurrency;
use Inertia\Inertia;

class AdminUserController extends Controller
{
    use AppliesTableParams;

    public function __construct(
        private AdminServiceInterface $adminService,
        private SubscriptionServiceInterface $subscriptionService,
        private FileServiceInterface $fileService,
        private DirectoryServiceInterface $directoryService
    ) {}

    public function index(AdminUserIndexRequest $request)
    {
        $params = $this->tableParams($request);
        $params['sort'] = $request->getSorts();
        $params['filters'] = $request->getFilters();

        $users = $this->adminService->paginateUsersWithParams($params);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => $this->tableFiltersWith($params, $request, ['role']),
        ]);
    }

    public function store(StoreUserRequest $request)
    {
        $user = $this->adminService->createUser($request->only(['name', 'email', 'password', 'role', 'storage_limit']));

        $defaultPackage = $this->subscriptionService->getDefaultPackage();

        if ($defaultPackage) {
            $this->subscriptionService->subscribe($user->id, $defaultPackage->id);
        }

        return redirect()->route('admin.users.index')->with('success', 'User created');
    }

    public function show(string $id)
    {
        $user = $this->adminService->getUserById($id);

        [$files, $directories, $fileCount, $directoryCount, $packages] = Concurrency::run([
            fn () => $this->fileService->getLatestForUser($user->id, 10),
            fn () => $this->directoryService->getLatestForUser($user->id, 10),
            fn () => $this->fileService->countAllWithTrashed($user->id),
            fn () => $this->directoryService->countAllWithTrashed($user->id),
            fn () => $this->subscriptionService->getAllPackages(),
        ]);

        return Inertia::render('Admin/Users/Show', [
            'user' => $user,
            'files' => $files,
            'directories' => $directories,
            'stats' => [
                'files' => $fileCount,
                'directories' => $directoryCount,
                'storage_used' => (int) ($user->profile?->storage_used ?? 0),
                'storage_limit' => $user->profile?->storage_limit,
            ],
            'packages' => $packages,
        ]);
    }

    public function update(UpdateUserRequest $request, string $id)
    {
        $user = $this->adminService->findUserById($id);

        $this->adminService->updateUser($user, $request->only('name', 'email'));

        return redirect()->back()->with('success', 'User updated');
    }

    public function changeRole(ChangeRoleRequest $request, string $id)
    {
        $user = $this->adminService->findUserById($id);

        $this->adminService->changeRole($user, Role::from($request->validated('role')));

        return redirect()->back()->with('success', 'Role changed');
    }

    public function setQuota(SetQuotaRequest $request, string $id)
    {
        $user = $this->adminService->findUserById($id);

        $this->adminService->setQuota($user, $request->storage_limit);

        return redirect()->back()->with('success', 'Quota updated');
    }

    public function destroy(string $id)
    {
        $user = $this->adminService->findUserById($id);

        $this->adminService->deleteUser($user);

        return redirect()->route('admin.users.index')->with('success', 'User deleted');
    }
}
