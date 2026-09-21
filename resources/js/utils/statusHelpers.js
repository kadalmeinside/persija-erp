
export const getStatusBadgeClass = (status) => {
    switch(status) {
        case 'Draft': return 'bg-gray-100 text-gray-800 border-gray-200';
        case 'Pending Approval': return 'bg-yellow-50 text-yellow-700 border-yellow-200';
        case 'Approved': return 'bg-blue-50 text-blue-700 border-blue-200';
        case 'Paid': return 'bg-green-50 text-green-700 border-green-200';
        case 'Rejected': return 'bg-red-50 text-red-700 border-red-200';
        case 'Settled': return 'bg-purple-50 text-purple-700 border-purple-200';
        default: return 'bg-gray-50 text-gray-600 border-gray-200';
    }
};

export const getStepColor = (status) => {
    if (['Approved', 'Paid', 'Settled'].includes(status)) return 'bg-green-500 border-green-500 text-white';
    if (status === 'Rejected') return 'bg-red-500 border-red-500 text-white';
    if (status === 'Requested Change') return 'bg-yellow-500 border-yellow-500 text-white';
    return 'bg-gray-200 border-gray-200 text-gray-500';
};
