<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Concerns\AppliesTableParams;
use App\Models\Directory;
use App\Models\File;
use App\Models\StoragePackage;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\Interfaces\AdminServiceInterface;
use App\Services\Interfaces\SubscriptionServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class AdminUserController extends Controller
{
    use AppliesTableParams;

    public function __construct(
        private AdminServiceInterface $adminService,
        private SubscriptionServiceInterface $subscriptionService
    ) {}

    public function index(Request $request)
    {
        $params = $this->tableParams($request);

        $users = $this->applyTableQuery(
            $this->applyFilters(
                User::query()->with('profile')->withCount('files'),
                $request,
                ['role' => 'role']
            ),
            $params,
            searchable: ['name', 'email'],
            sortable: [
                'name' => 'name',
                'email' => 'email',
                'role' => 'role',
                'files' => 'files_count',
                'storage_used' => \DB::raw('(SELECT COALESCE(storage_used,0) FROM profiles WHERE profiles.user_id = users.id)'),
                'storage_limit' => \DB::raw('(SELECT COALESCE(storage_limit,0) FROM profiles WHERE profiles.user_id = users.id)'),
                'created_at' => 'created_at',
            ],
            defaultSort: 'created_at',
            defaultDirection: 'desc',
        )->paginate($params['per_page'])->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => $this->tableFiltersWith($params, $request, ['role']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['sometimes', 'in:member,admin'],
            'storage_limit' => ['sometimes', 'nullable', 'integer', 'min:0'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? 'member',
            'email_verified_at' => now(),
        ]);

        $user->profile()->create([
            'storage_limit' => $request->storage_limit,
            'storage_used' => 0,
        ]);

        $defaultPackage = StoragePackage::query()->where('is_default', true)->first();

        if ($defaultPackage) {
            $this->subscriptionService->subscribe($user->id, $defaultPackage->id);
        }

        return redirect()->route('admin.users.index')->with('success', 'User created');
    }

    public function show(string $id)
    {
        $user = User::with(['profile', 'subscriptions.storagePackage'])->findOrFail($id);

        return Inertia::render('Admin/Users/Show', [
            'user' => $user,
            'files' => File::with(['media', 'directory'])
                ->where('user_id', $user->id)
                ->withTrashed()
                ->latest()
                ->limit(10)
                ->get(),
            'directories' => Directory::withCount('files')
                ->where('user_id', $user->id)
                ->withTrashed()
                ->latest()
                ->limit(10)
                ->get(),
            'stats' => [
                'files' => File::withTrashed()->where('user_id', $user->id)->count(),
                'directories' => Directory::withTrashed()->where('user_id', $user->id)->count(),
                'storage_used' => (int) ($user->profile?->storage_used ?? 0),
                'storage_limit' => $user->profile?->storage_limit,
            ],
            'packages' => StoragePackage::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,'.$user->id],
        ]);

        $this->adminService->updateUser($user, $request->only('name', 'email'));

        return redirect()->back()->with('success', 'User updated');
    }

    public function changeRole(Request $request, string $id)
    {
        $request->validate(['role' => ['required', 'in:member,admin']]);

        $user = User::findOrFail($id);

        $this->adminService->changeRole($user, $request->role);

        return redirect()->back()->with('success', 'Role changed');
    }

    public function setQuota(Request $request, string $id)
    {
        $request->validate(['storage_limit' => ['nullable', 'integer', 'min:0']]);

        $user = User::findOrFail($id);

        $this->adminService->setQuota($user, $request->storage_limit);

        return redirect()->back()->with('success', 'Quota updated');
    }

    public function destroy(string $id)
    {
        $user = User::findOrFail($id);

        $this->adminService->deleteUser($user);

        return redirect()->route('admin.users.index')->with('success', 'User deleted');
    }
}
