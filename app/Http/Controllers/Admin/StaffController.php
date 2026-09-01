<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Application\Staff\Actions\CreateStaff;
use App\Application\Staff\Actions\DeleteStaff;
use App\Application\Staff\Actions\UpdateStaff;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStaffRequest;
use App\Http\Requests\Admin\UpdateStaffRequest;
use App\Models\Service;
use App\Repositories\Contracts\StaffRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

final class StaffController extends Controller
{
    public function __construct(
        private readonly StaffRepositoryInterface $staff,
        private readonly CreateStaff $createStaff,
        private readonly UpdateStaff $updateStaff,
        private readonly DeleteStaff $deleteStaff,
    ) {}

    private function ensureTenantAdmin(): void
    {
        abort_unless(auth()->user()?->hasRole('Admin Tenant'), 403);
    }

    public function index()
    {
        $staffMembers = $this->staff->all();
        $services = Service::orderBy('name')->get();

        return view('admin.staff.index', compact('staffMembers', 'services'));
    }

    public function show(int $id): JsonResponse
    {
        try {
            $member = $this->staff->findWithRelations($id, ['services', 'schedules']);

            return response()->json(['success' => true, 'data' => $member]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Not found')], 404);
        }
    }

    public function store(StoreStaffRequest $request): JsonResponse
    {
        $this->ensureTenantAdmin();

        try {
            $data = $request->validated();
            $member = $this->createStaff->execute($data);
            $password = explode('@', $data['email'])[0] . '123';

            return response()->json([
                'success' => true,
                'message' => __('Staff member created. Default password: ') . $password,
                'data' => $member,
                'default_password' => $password,
            ]);
        } catch (\Exception $e) {
            Log::error('storeStaff: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(UpdateStaffRequest $request, int $id): JsonResponse
    {
        $this->ensureTenantAdmin();

        try {
            $member = $this->staff->findById($id);
            $data = $request->validated();
            $this->updateStaff->execute($member, $data);

            return response()->json([
                'success' => true,
                'message' => __('Staff member updated.'),
                'data' => $member->fresh(['services', 'schedules']),
            ]);
        } catch (\Exception $e) {
            Log::error('updateStaff: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $this->ensureTenantAdmin();

        try {
            $member = $this->staff->findById($id);
            $this->deleteStaff->execute($member);

            return response()->json(['success' => true, 'message' => __('Staff member deleted.')]);
        } catch (\Exception $e) {
            Log::error('destroyStaff: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function bySpecialization(string $specialization): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->staff->getBySpecialization($specialization),
        ]);
    }

    public function services(int $id): JsonResponse
    {
        try {
            $member = $this->staff->findWithRelations($id, ['services']);

            return response()->json([
                'success' => true,
                'data' => $member->services->map(
                    fn ($service) => $service->only(['id', 'name', 'name_ar', 'duration', 'price'])
                ),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Not found')], 404);
        }
    }

    public function byService(int $serviceId): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->staff->getByService($serviceId),
        ]);
    }

    public function schedule(int $staffId): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->staff->getSchedule($staffId),
        ]);
    }
}