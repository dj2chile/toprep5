<!-- Results display -->
<div class="bg-white shadow rounded-lg overflow-hidden">
    <!-- Desktop view -->
    <div class="desktop-view overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Model
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Opcje naprawy
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Numer seryjny
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Telefon
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Status
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Data przyjęcia
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Akcje
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach($repairs as $repair): ?>
                    <tr class="hover:bg-gray-50" onclick="window.location.href='repair_details.php?id=<?php echo $repair['id']; ?>'">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php echo htmlspecialchars($repair['device_model']); ?>
                        </td>
                        <td class="px-6 py-4">
                            <div class="repair-options flex flex-col space-y-1">
                                <?php foreach(getRepairOptions($repair) as $option): ?>
                                    <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                        <?php echo htmlspecialchars($option); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php echo htmlspecialchars($repair['serial_number']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <a href="#" onclick="event.stopPropagation(); composeSms('<?php echo htmlspecialchars($repair['phone_number']); ?>')" 
                               class="text-blue-600 hover:text-blue-900 flex items-center">
                                <?php echo htmlspecialchars($repair['phone_number']); ?>
                                <!-- Show contact preference icons -->
                                <?php if(!empty($repair['prefer_phone_contact']) || !empty($repair['prefer_sms_contact'])): ?>
                                    <span class="ml-2 flex">
                                        <?php if(!empty($repair['prefer_phone_contact'])): ?>
                                            <i class="fas fa-phone text-pink-600 mr-1" title="Preferuje kontakt telefoniczny"></i>
                                        <?php endif; ?>
                                        <?php if(!empty($repair['prefer_sms_contact'])): ?>
                                            <i class="fas fa-envelope text-pink-600" title="Preferuje kontakt SMS"></i>
                                        <?php endif; ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap status-cell">
                            <span class="status-label px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?php echo $STATUS_COLORS[$repair['status']] ?? 'bg-gray-100 text-gray-800'; ?>"
                                  onclick="event.stopPropagation(); toggleStatusDropdown(event, <?php echo $repair['id']; ?>)">
                                <?php echo htmlspecialchars($repair['status']); ?>
                            </span>
                            <div id="status-dropdown-<?php echo $repair['id']; ?>" class="status-dropdown">
                                <?php foreach($STATUS_COLORS as $status => $color): 
                                    if($status !== $repair['status']): ?>
                                    <button type="button"
                                            class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                            onclick="event.stopPropagation(); updateStatus(<?php echo $repair['id']; ?>, '<?php echo htmlspecialchars($status); ?>')">
                                        <?php echo htmlspecialchars($status); ?>
                                    </button>
                                <?php endif; endforeach; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo date('d.m.Y H:i', strtotime($repair['created_at'])); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button onclick="event.stopPropagation(); handleDelete(<?php echo $repair['id']; ?>)" 
                                    class="text-red-600 hover:text-red-900">
                                Usuń
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Mobile view -->
    <div class="mobile-view">
        <?php foreach($repairs as $repair): ?>
            <div class="p-4 border-b border-gray-200" onclick="window.location.href='repair_details.php?id=<?php echo $repair['id']; ?>'">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">
                            <?php echo htmlspecialchars($repair['device_model']); ?>
                        </h3>
                        <p class="text-sm text-gray-500">
                            <?php echo htmlspecialchars($repair['serial_number']); ?>
                        </p>
                    </div>
                    <div class="status-cell">
                        <span class="status-label px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                            <?php echo $STATUS_COLORS[$repair['status']] ?? 'bg-gray-100 text-gray-800'; ?>"
                              onclick="event.stopPropagation(); toggleStatusDropdown(event, <?php echo $repair['id']; ?>)">
                            <?php echo htmlspecialchars($repair['status']); ?>
                        </span>
                        <div id="status-dropdown-mobile-<?php echo $repair['id']; ?>" class="status-dropdown">
                            <?php foreach($STATUS_COLORS as $status => $color): 
                                if($status !== $repair['status']): ?>
                                <button type="button"
                                        class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                        onclick="event.stopPropagation(); updateStatus(<?php echo $repair['id']; ?>, '<?php echo htmlspecialchars($status); ?>')">
                                    <?php echo htmlspecialchars($status); ?>
                                </button>
                            <?php endif; endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="mt-4 flex justify-between items-center">
                    <a href="#" onclick="event.stopPropagation(); composeSms('<?php echo htmlspecialchars($repair['phone_number']); ?>')" 
                       class="text-blue-600 hover:text-blue-900 flex items-center">
                        <?php echo htmlspecialchars($repair['phone_number']); ?>
                        <!-- Show contact preference icons -->
                        <?php if(!empty($repair['prefer_phone_contact']) || !empty($repair['prefer_sms_contact'])): ?>
                            <span class="ml-2 flex">
                                <?php if(!empty($repair['prefer_phone_contact'])): ?>
                                    <i class="fas fa-phone text-pink-600 mr-1" title="Preferuje kontakt telefoniczny"></i>
                                <?php endif; ?>
                                <?php if(!empty($repair['prefer_sms_contact'])): ?>
                                    <i class="fas fa-envelope text-pink-600" title="Preferuje kontakt SMS"></i>
                                <?php endif; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <button onclick="event.stopPropagation(); handleDelete(<?php echo $repair['id']; ?>)" 
                            class="text-red-600 hover:text-red-900">
                        Usuń
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>