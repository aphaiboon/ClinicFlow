import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Form } from '@inertiajs/react';
import { type ExamRoom } from '@/types';
import { type RouteDefinition } from '@/wayfinder';
import { useState } from 'react';

interface ExamRoomFormProps {
    room?: ExamRoom;
    route: RouteDefinition<'post'> | RouteDefinition<'put'>;
    processing?: boolean;
}

export function ExamRoomForm({ room, route, processing = false }: ExamRoomFormProps) {
    const [equipment, setEquipment] = useState<string[]>(room?.equipment || []);
    const [newEquipment, setNewEquipment] = useState('');
    const [isActive, setIsActive] = useState(room?.is_active ?? true);

    const addEquipment = () => {
        if (newEquipment.trim()) {
            setEquipment([...equipment, newEquipment.trim()]);
            setNewEquipment('');
        }
    };

    const removeEquipment = (index: number) => {
        setEquipment(equipment.filter((_, i) => i !== index));
    };

    return (
        <Form {...route.form()} className="space-y-6">
            {({ processing: formProcessing, errors }) => (
                <>
                    {equipment.map((item, index) => (
                        <input key={index} type="hidden" name={`equipment[${index}]`} value={item} />
                    ))}
                    <input type="hidden" name="is_active" value={isActive ? '1' : '0'} />

                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="room_number">Room Number *</Label>
                            <Input
                                id="room_number"
                                name="room_number"
                                defaultValue={room?.room_number}
                                required
                            />
                            <InputError message={errors.room_number} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="name">Name *</Label>
                            <Input
                                id="name"
                                name="name"
                                defaultValue={room?.name}
                                required
                            />
                            <InputError message={errors.name} />
                        </div>
                    </div>

                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="floor">Floor</Label>
                            <Input
                                id="floor"
                                type="number"
                                name="floor"
                                defaultValue={room?.floor}
                                min={1}
                            />
                            <InputError message={errors.floor} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="capacity">Capacity *</Label>
                            <Input
                                id="capacity"
                                type="number"
                                name="capacity"
                                defaultValue={room?.capacity || 1}
                                required
                                min={1}
                                max={10}
                            />
                            <InputError message={errors.capacity} />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label>Equipment</Label>
                        <div className="space-y-2">
                            {equipment.map((item, index) => (
                                <div key={index} className="flex items-center justify-between rounded-md border p-2">
                                    <span>{item}</span>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => removeEquipment(index)}
                                    >
                                        Remove
                                    </Button>
                                </div>
                            ))}
                            <div className="flex gap-2">
                                <Input
                                    value={newEquipment}
                                    onChange={(e) => setNewEquipment(e.target.value)}
                                    placeholder="Add equipment item"
                                    onKeyDown={(e) => {
                                        if (e.key === 'Enter') {
                                            e.preventDefault();
                                            addEquipment();
                                        }
                                    }}
                                />
                                <Button type="button" variant="outline" onClick={addEquipment}>
                                    Add
                                </Button>
                            </div>
                        </div>
                        <InputError message={errors.equipment} />
                    </div>

                    <div className="flex items-center gap-2">
                        <Checkbox
                            id="is_active"
                            checked={isActive}
                            onCheckedChange={(checked) => setIsActive(checked === true)}
                        />
                        <Label htmlFor="is_active" className="font-normal cursor-pointer">
                            Active
                        </Label>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="notes">Notes</Label>
                        <textarea
                            id="notes"
                            name="notes"
                            defaultValue={room?.notes}
                            rows={4}
                            className="flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50"
                        />
                        <InputError message={errors.notes} />
                    </div>

                    <div className="flex gap-4">
                        <Button type="submit" disabled={formProcessing || processing}>
                            {formProcessing || processing
                                ? 'Saving...'
                                : room
                                  ? 'Update Room'
                                  : 'Create Room'}
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}
